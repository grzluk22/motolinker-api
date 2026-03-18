<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\CategoryLanguage;
use App\Entity\Language;
use App\Entity\ExternalDatabase;
use App\Entity\Image;
use App\Entity\ImportJob;
use App\Message\PrestashopImportMessage;
use App\Repository\CategoryRepository;
use App\Repository\CategoryLanguageRepository;
use App\Repository\LanguageRepository;
use App\Repository\SettingRepository;
use App\Service\ImageUploadService;
use App\Service\PrestashopClientService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class PrestashopImportHandler
{
    private const DEFAULT_BATCH_SIZE = 50;
    private const SETTING_KEY_BATCH_SIZE = 'prestashop_import_batch_size';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PrestashopClientService $prestashopClientService,
        private readonly ImageUploadService $imageUploadService,
        private readonly SettingRepository $settingRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly CategoryLanguageRepository $categoryLanguageRepository,
        private readonly LanguageRepository $languageRepository,
        private readonly HubInterface $hub,
        private readonly string $mercureTopicBaseUrl,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(PrestashopImportMessage $message): void
    {
        $task = $this->entityManager->getRepository(ImportJob::class)->find($message->getImportJobId());

        if (!$task || in_array($task->getStatus(), [
            ImportJob::STATUS_CANCELLED,
            ImportJob::STATUS_COMPLETED,
            ImportJob::STATUS_FAILED,
        ])) {
            return;
        }

        $task->setStatus(ImportJob::STATUS_PROCESSING);
        $this->entityManager->flush();

        try {
            $database = $task->getExternalDatabase();
            if (!$database) {
                throw new \RuntimeException('No Prestashop database connection assigned to this import job.');
            }

            if ($task->getImportType() === 'categories') {
                $this->processCategoriesImport($task, $database);
                return;
            }

            $batchSize = $this->resolveBatchSize();

            $requestedProductIds = $task->getSourceIds() ?? [];
            $fetchAll             = empty($requestedProductIds);

            // Preprocess filters
            $jobFilters = $task->getSourceFilters() ?? [];
            $nonExistingConstraint = !empty($jobFilters['non_existing']) && $jobFilters['non_existing'] !== 'false' && $jobFilters['non_existing'] !== false;
            unset($jobFilters['non_existing']); // Nie doczepiaj wymyślonego klucza do zapytań

            $filters = array_merge(['active' => '1'], $jobFilters);

            if (!$fetchAll) {
                // Pobieramy ID + Referencje tylko dla wybranych produktów
                $tempFilters = array_merge($filters, ['id' => '[' . implode('|', $requestedProductIds) . ']']);
                $idRefMap = $this->prestashopClientService->getProductIds($database, $tempFilters);
            } else {
                // Pobieramy wszystkie aktywne id + referencje wg wszystkich filtrów
                $idRefMap = $this->prestashopClientService->getProductIds($database, $filters);
            }

            $articleRepository = $this->entityManager->getRepository(Article::class);

            if ($nonExistingConstraint) {
                $existingCodes = $articleRepository->findAllArticleCodes(); // Pobiera same kody, super szybkie
                $existingCodesMap = array_flip($existingCodes);
                
                $allProductIds = [];
                foreach ($idRefMap as $id => $ref) {
                    if ($ref && !isset($existingCodesMap[$ref])) {
                        $allProductIds[] = $id; // Zachowaj tylko te, których referencji nie ma w bazie
                    }
                }
            } else {
                $allProductIds = array_keys($idRefMap);
            }

            $totalRows = count($allProductIds);
            
            if ($totalRows === 0) {
            	$task->setTotalRows(0);
                $task->setStatus(ImportJob::STATUS_COMPLETED);
                $this->entityManager->flush();
                $this->publishProgress($task);
                return;
            }

            $task->setTotalRows($totalRows);
            $this->publishProgress($task);

            $processed         = 0;

            // Process IDs in chunks instead of relying on offset pagination
            $idChunks = array_chunk($allProductIds, $batchSize);

            // Fetch Prestashop categories map to locally map them
            $psCategoriesMap = [];
            $psIdToLocalIdMap = [];
            $localCategoriesMap = $this->buildLocalCategoriesMap();
            $defaultLanguage = $this->getDefaultLanguage();

            try {
                $psCategoriesRaw = $this->prestashopClientService->getCategories($database);
                $dummyProcessed = 0;
                $this->syncCategoryTree($psCategoriesRaw, null, $localCategoriesMap, $psIdToLocalIdMap, $defaultLanguage, 'skip', null, $dummyProcessed);
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $this->logger->warning('Could not fetch categories from Prestashop', ['error' => $e->getMessage()]);
            }

            foreach ($idChunks as $chunkedIds) {
                // Fetch full products data for this chunk
                $chunkFilters = array_merge($filters, ['id' => '[' . implode('|', $chunkedIds) . ']']);
                unset($chunkFilters['quantity_min'], $chunkFilters['quantity_max'], $chunkFilters['quantity']);
                // We ask for limit = batchSize, but with specific IDs it just limits to these IDs
                $productsRaw = $this->prestashopClientService->getProducts($database, $chunkFilters, $batchSize, 0);

                if (empty($productsRaw)) {
                	// Products might have been deleted, but we still advance the processed count
                	$processed += count($chunkedIds);
                    $task->setProcessedRows($processed);
                    continue;
                }

                foreach ($productsRaw as $psProduct) {
                    $reference = $psProduct['reference'] ?? null;
                    if (!$reference) {
                        $processed++;
                        continue; // Skip products without a reference code
                    }

                    // Upsert: find existing or create new Article
                    $article = $articleRepository->findOneBy(['code' => $reference]);
                    if (!$article) {
                        $article = new Article();
                        $article->setCode($reference);
                    }

                    $article->setName(strip_tags((string) $this->extractMultilangString($psProduct['name'] ?? '')));
                    $article->setDescription(strip_tags((string) $this->extractMultilangString($psProduct['description'] ?? '')));
                    $article->setPrice((float) ($psProduct['price'] ?? 0));
                    $article->setEan13($psProduct['ean13'] ?? null);
                    
                    // Assign category
                    $psCategoryId = (int) ($psProduct['id_category_default'] ?? 0);
                    $localCategoryId = $psIdToLocalIdMap[$psCategoryId] ?? null;

                    if ($localCategoryId !== null) {
                        $article->setIdCategory($localCategoryId);
                    } else {
                        // Keep PS ID as fallback or 0
                        $article->setIdCategory(0);
                    }

                    $this->entityManager->persist($article);
                    $this->entityManager->flush(); // Flush now so article has an ID for image saving

                    // Download and persist product images
                    $this->importProductImages($psProduct, $article, $database);

                    $processed++;
                    $task->setProcessedRows($processed);
                }

                // If some items in the chunk were not returned by API (e.g. deleted/not active), 
                // we still need to increment processed count by the ones that were completely missing.
                $chunkIndex = array_search($chunkedIds, $idChunks);
                $expectedProcessed = ($chunkIndex + 1) * $batchSize;
                
                if ($processed < min($expectedProcessed, $totalRows)) {
                	$processed = min($expectedProcessed, $totalRows); // Fast forward processed count
                	$task->setProcessedRows($processed);
                }

                // Flush + notify once per batch
                $this->entityManager->flush();
                $this->publishProgress($task);
                // Clear identity map to free memory (clears ALL tracked entities)
                $this->entityManager->clear();

                // Reload task after clear() to avoid detached entity issues
                $task = $this->entityManager->getRepository(ImportJob::class)->find($message->getImportJobId());
                if (!$task || in_array($task->getStatus(), [ImportJob::STATUS_CANCELLED, ImportJob::STATUS_PAUSED, ImportJob::STATUS_PAUSING, ImportJob::STATUS_CANCELLING])) {
                    return; // Stop if cancelled or paused
                }
            }

            $task->setStatus(ImportJob::STATUS_COMPLETED);
            $this->entityManager->flush();
            $this->publishProgress($task);

        } catch (\Throwable $e) {
            if (!$this->entityManager->isOpen()) {
                throw $e;
            }

            $task->setStatus(ImportJob::STATUS_FAILED);
            $task->setErrorMessage($e->getMessage());
            $this->entityManager->flush();
            $this->publishProgress($task, $e->getMessage());
        }
    }

    private function processCategoriesImport(ImportJob $task, ExternalDatabase $database): void
    {
        try {
            $user = $task->getUser();
            $existingActionSetting = null;
            if ($user) {
                $existingActionSetting = $this->settingRepository->getSetting('import_category_existing_action', $user);
            }
            if (!$existingActionSetting) {
                $existingActionSetting = $this->settingRepository->getGlobalSetting('import_category_existing_action') ?? 'skip';
            }

            $psCategoriesTree = $this->prestashopClientService->getCategories($database);
            $requestedIds = $task->getSourceIds() ?? [];
            
            $totalCategories = $this->countCategoryTree($psCategoriesTree, $requestedIds);
            $task->setTotalRows($totalCategories);
            $this->publishProgress($task);

            if ($totalCategories === 0) {
                $task->setStatus(ImportJob::STATUS_COMPLETED);
                $this->entityManager->flush();
                $this->publishProgress($task);
                return;
            }

            $localCategoriesMap = $this->buildLocalCategoriesMap();
            $defaultLanguage = $this->getDefaultLanguage();
            $psIdToLocalIdMap = [];

            $processed = 0;
            $this->syncCategoryTree($psCategoriesTree, null, $localCategoriesMap, $psIdToLocalIdMap, $defaultLanguage, (string) $existingActionSetting, $task, $processed, $requestedIds);

            $task->setStatus(ImportJob::STATUS_COMPLETED);
            $this->entityManager->flush();
            $this->publishProgress($task);

        } catch (\Throwable $e) {
            $task->setStatus(ImportJob::STATUS_FAILED);
            $task->setErrorMessage($e->getMessage());
            $this->entityManager->flush();
            $this->publishProgress($task, $e->getMessage());
        }
    }

    private function countCategoryTree(array $tree, array $requestedIds = []): int
    {
        $count = 0;
        foreach ($tree as $category) {
            if (empty($requestedIds) || in_array($category->id, $requestedIds)) {
                $count++;
            }
            if (!empty($category->children)) {
                $count += $this->countCategoryTree($category->children, $requestedIds);
            }
        }
        return $count;
    }

    /**
     * @param \App\HttpResponseModel\PrestashopCategory[] $tree
     */
    private function syncCategoryTree(
        array $tree,
        ?int $parentLocalId,
        array &$localCategoriesMap,
        array &$psIdToLocalIdMap,
        Language $defaultLanguage,
        string $existingActionSetting,
        ?ImportJob $task = null,
        int &$processed = 0,
        array $requestedIds = []
    ): void {
        foreach ($tree as $psCategory) {
            $categoryName = $psCategory->name;
            $catNameLower = mb_strtolower((string)$categoryName);
            $localCategoryId = null;

            $shouldProcess = empty($requestedIds) || in_array($psCategory->id, $requestedIds);

            if ($shouldProcess) {
                if (isset($localCategoriesMap[$catNameLower])) {
                    $localCategoryId = $localCategoriesMap[$catNameLower];
                    if ($existingActionSetting === 'update') {
                        $cat = $this->categoryRepository->find($localCategoryId);
                        if ($cat && $parentLocalId !== null) {
                            $cat->setIdParent($parentLocalId);
                            $this->entityManager->persist($cat);
                        }
                    }
                } else {
                    $category = new Category();
                    $category->setIdParent($parentLocalId ?? 0);
                    $this->entityManager->persist($category);
                    $this->entityManager->flush();

                    $categoryLanguage = new CategoryLanguage();
                    $categoryLanguage->setIdCategory($category->getId());
                    $categoryLanguage->setIdLanguage($defaultLanguage->getId());
                    $categoryLanguage->setName($categoryName);
                    $categoryLanguage->setDescription('');
                    $this->entityManager->persist($categoryLanguage);
                    $this->entityManager->flush();

                    $localCategoryId = $category->getId();
                    $localCategoriesMap[$catNameLower] = $localCategoryId;
                }

                $psIdToLocalIdMap[$psCategory->id] = $localCategoryId;

                if ($task !== null) {
                    $processed++;
                    $task->setProcessedRows($processed);

                    if ($processed % 20 === 0) {
                        $this->publishProgress($task);
                    }
                }
            } else {
                // Not processing this specific category, but we still check if it exists 
                // locally so its children (if requested) can link to it as their parent.
                $localCategoryId = $localCategoriesMap[$catNameLower] ?? null;
            }

            if (!empty($psCategory->children)) {
                $this->syncCategoryTree(
                    $psCategory->children, 
                    $localCategoryId, 
                    $localCategoriesMap, 
                    $psIdToLocalIdMap, 
                    $defaultLanguage, 
                    $existingActionSetting, 
                    $task, 
                    $processed,
                    $requestedIds
                );
            }
        }
    }

    private function buildLocalCategoriesMap(): array
    {
        $map = [];
        // Raw DB query to avoid loading entities into memory
        $conn = $this->entityManager->getConnection();
        $sql = 'SELECT id_category, name FROM category_language';
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        foreach ($result as $row) {
            $catNameLower = mb_strtolower((string) $row['name']);
            // If duplicate names exist, first one wins
            if (!isset($map[$catNameLower])) {
                $map[$catNameLower] = (int) $row['id_category'];
            }
        }

        return $map;
    }

    private function getDefaultLanguage(): Language
    {
        // Prio 1: pl
        $lang = $this->languageRepository->findOneBy(['isoCode' => 'pl']);
        if (!$lang) {
            $lang = $this->languageRepository->findOneBy(['isoCode' => 'en']);
        }
        if (!$lang) {
            $langs = $this->languageRepository->findAll();
            $lang = !empty($langs) ? $langs[0] : null;
        }

        if (!$lang) {
            // Create fallback language if none exists
            $lang = new Language();
            $lang->setName('Polski');
            $lang->setIsoCode('pl');
            $this->entityManager->persist($lang);
            $this->entityManager->flush();
        }

        return $lang;
    }

    /**
     * Downloads and persists all images for a given Prestashop product.
     * Skips existing images that are already imported (matched by URL).
     * Errors per image are swallowed so a single bad image does not abort the import.
     *
     * @param array<string, mixed> $psProduct
     */
    private function importProductImages(
        array $psProduct,
        Article $article,
        ExternalDatabase $database
    ): void {
        $imageAssociations = $psProduct['associations']['images'] ?? [];
        if (!is_array($imageAssociations) || empty($imageAssociations)) {
            return;
        }

        $baseUrl = rtrim($database->getApiUrl(), '/');
        $apiKey  = $database->getApiKey();
        $productId = (int) $psProduct['id'];

        // Collect existing image URLs to avoid re-downloading
        $existingUrls = array_map(
            static fn(Image $img): string => $img->getUrl() ?? '',
            $article->getImages()->toArray()
        );

        $position = count($existingUrls); // Start positions after already-existing images

        foreach ($imageAssociations as $index => $imgData) {
            if (!isset($imgData['id'])) {
                continue;
            }

            $prestashopImageUrl = sprintf(
                '%s/api/images/products/%d/%d?ws_key=%s',
                $baseUrl,
                $productId,
                (int) $imgData['id'],
                $apiKey
            );

            try {
                $isMain = ($index === 0 && $article->getImages()->isEmpty());
                $image  = $this->imageUploadService->downloadFromUrl($prestashopImageUrl, $article, $position, $isMain);
                $article->addImage($image);
                $this->entityManager->persist($image);
                $position++;
            } catch (\Throwable $e) {
                // Do not abort import for a single image failure — continue to next image
                // Log this error
                $this->logger->error('Failed to import image for article ' . $article->getId(), [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Reads batch size from settings, falls back to DEFAULT_BATCH_SIZE.
     */
    private function resolveBatchSize(): int
    {
        $value = $this->settingRepository->getGlobalSetting(self::SETTING_KEY_BATCH_SIZE);

        if ($value !== null && ctype_digit($value) && (int) $value > 0) {
            return (int) $value;
        }

        return self::DEFAULT_BATCH_SIZE;
    }

    private function publishProgress(ImportJob $task, ?string $error = null): void
    {
        try {
            $payload = [
                'id'            => $task->getId(),
                'status'        => $task->getStatus(),
                'processedRows' => $task->getProcessedRows(),
                'totalRows'     => $task->getTotalRows(),
            ];

            if ($error !== null) {
                $payload['error'] = $error;
            }

            $update = new Update(
                rtrim($this->mercureTopicBaseUrl, '/') . '/import/progress/' . $task->getId(),
                json_encode($payload)
            );
            $this->hub->publish($update);

        } catch (\Exception) {
            // Mercure connection errors must not abort the import
        }
    }

    private function extractMultilangString(mixed $fieldData): string
    {
        if (is_string($fieldData)) {
            return $fieldData;
        }

        if (is_array($fieldData)) {
            if (isset($fieldData[0]['value'])) {
                return $fieldData[0]['value'];
            }
            return current($fieldData) !== false ? (string) current($fieldData) : '';
        }

        return '';
    }
}
