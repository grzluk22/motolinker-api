<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticleLanguage;
use App\Entity\ArticleEan;
use App\Repository\ArticleLanguageRepository;
use App\Repository\ArticleEanRepository;
use App\Repository\ArticleRepository;
use App\Repository\ImageRepository;
use App\Repository\LanguageRepository;
use App\Service\ImageUploadService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use function Symfony\Config\toArray;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use App\Entity\Language;
use App\HttpRequestModel\ArticleGetByRequest;
use App\HttpRequestModel\ArticleCreateRequest;
use App\HttpRequestModel\ArticleUpdateRequest;
use App\HttpResponseModel\ArticleListResponse;
use App\HttpResponseModel\ArticleDetailResponse;
use App\HttpResponseModel\MessageResponse;
use App\HttpRequestModel\ArticleBulkDeleteRequest;

class ArticleController extends AbstractController
{
    /**
     * WyĹ›wietla liste artykuĹ‚Ăłw
     */
    #[OA\Tag(name: "Article")]
    #[OA\Response(
        response: 200,
        description: "Lista artykuĹ‚Ăłw",
        content: new Model(type: ArticleListResponse::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Brak artykuĹ‚Ăłw"
    )]
    #[Route('/article', name: 'app_article_get', methods: ["GET"])]
    public function index(ArticleRepository $articleRepository, ImageRepository $imageRepository, ImageUploadService $imageUploadService): JsonResponse
    {
        $result = $articleRepository->findAll();
        $total = count($result);
        
        if (empty($result)) {
            return new JsonResponse(['data' => [], 'total' => 0]);
        }
        
        // Pobierz wszystkie gĹ‚Ăłwne zdjÄ™cia w jednym zapytaniu
        $articleIds = array_map(fn($article) => $article->getId(), $result);
        $mainImages = $imageRepository->findMainImagesForArticles($articleIds);
        
        $withThumbnails = [];
        foreach ($result as $article) {
            $articleId = $article->getId();
            $mainImage = $mainImages[$articleId] ?? null;
            // PrzekaĹĽ articleId bezpoĹ›rednio, aby uniknÄ…Ä‡ problemĂłw z relacjami Doctrine
            $thumbnailUrl = $mainImage ? $imageUploadService->getThumbnailUrl($mainImage, $articleId) : null;
            
            // RÄ™cznie buduj tablicÄ™ z wĹ‚aĹ›ciwoĹ›ci Article
            $articleArray = [
                'id' => $article->id,
                'code' => $article->code,
                'ean13' => $article->ean13,
                'price' => $article->price,
                'id_category' => $article->id_category,
                'name' => $article->name,
                'description' => $article->description,
                'thumbnail_url' => $thumbnailUrl
            ];
            $withThumbnails[] = (object)$articleArray;
        }
        return new JsonResponse(['data' => $withThumbnails, 'total' => $total]);
    }


    /**
     * Zbiorcze usuwanie artykuĹĂłw
     */
    #[OA\Tag(name: "Article")]
    #[OA\RequestBody(
        description: "Lista ID lub filtry do usuniÄ™cia",
        required: true,
        content: new Model(type: ArticleBulkDeleteRequest::class)
    )]
    #[OA\Response(
        response: 200,
        description: "Wynik operacji",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string"),
                new OA\Property(property: "deleted_count", type: "integer"),
                new OA\Property(property: "errors", type: "array", items: new OA\Items(type: "string"))
            ]
        )
    )]
    #[Route('/article/bulk-delete', name: 'app_article_bulk_delete', methods: ["POST"])]
    public function bulkDelete(
        ManagerRegistry $doctrine, 
        ArticleRepository $articleRepository, 
        Request $request
    ): JsonResponse {
        $data = $request->toArray();
        $type = $data['type'] ?? 'list';
        $ids = [];

        if ($type === 'list') {
            $ids = $data['list'] ?? [];
        } elseif ($type === 'filtered') {
            $filters = $data['filters'] ?? [];
            // UĹĽywamy findByExtended bez limitu/offsetu by pobraÄ wszystkie pasujÄce
            $articles = $articleRepository->findByExtended($filters, [], 999999, 0);
            $ids = array_map(fn($a) => $a->getId(), $articles);
        }

        if (empty($ids)) {
            return new JsonResponse(['message' => 'Brak artykuĹĂłw do usuniÄcia', 'deleted_count' => 0], 200);
        }

        $entityManager = $doctrine->getManager();
        $session = $request->getSession();
        $total = count($ids);
        $deletedCount = 0;
        $errors = [];

        // Inicjalizacja postÄpu w sesji
        $session->set('bulk_delete_progress', [
            'current' => 0,
            'total' => $total,
            'status' => 'processing'
        ]);
        $session->save();

        foreach ($ids as $index => $id) {
            try {
                $article = $articleRepository->find($id);
                if ($article) {
                    // Usuwanie powiÄzaĹ rÄcznie
                    $this->deleteRelatedEntities($doctrine, $article);
                    $entityManager->remove($article);
                    $entityManager->flush();
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "BĹÄd przy ID $id: " . $e->getMessage();
            }

            // Aktualizacja postÄpu co 5 rekordĂłw lub na koĹcu
            if ($index % 5 === 0 || $index === $total - 1) {
                $session->set('bulk_delete_progress', [
                    'current' => $index + 1,
                    'total' => $total,
                    'status' => $index === $total - 1 ? 'completed' : 'processing'
                ]);
                $session->save();
            }
        }

        return new JsonResponse([
            'message' => 'ZakoĹczono zbiorcze usuwanie',
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ]);
    }

    /**
     * Pobiera status postÄpu zbiorczego usuwania
     */
    #[OA\Tag(name: "Article")]
    #[OA\Response(
        response: 200,
        description: "Status postÄpu",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "current", type: "integer"),
                new OA\Property(property: "total", type: "integer"),
                new OA\Property(property: "status", type: "string")
            ]
        )
    )]
    #[Route('/article/bulk-delete/status', name: 'app_article_bulk_delete_status', methods: ["GET"])]
    public function bulkDeleteStatus(Request $request): JsonResponse
    {
        $progress = $request->getSession()->get('bulk_delete_progress', [
            'current' => 0,
            'total' => 0,
            'status' => 'idle'
        ]);

        return new JsonResponse($progress);
    }
    /**
     * WyĹ›wietla przefiltrowana liste artykulow.
     */
    #[OA\Tag(name: "Article")]
    #[OA\RequestBody(
        description: "Filtry",
        required: true,
        content: new Model(type: ArticleGetByRequest::class)
    )]
    #[OA\Response(
        response: 200,
        description: "Lista artykuĹ‚Ăłw",
        content: new Model(type: ArticleListResponse::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono artykuĹ‚u"
    )]
    #[Route('/article/get', name: 'app_article_get_by', methods: ["POST"])]
    public function getBy(ArticleRepository $articleRepository, ImageRepository $imageRepository, ImageUploadService $imageUploadService, Request $request = null): JsonResponse
    {
        /* Najprostsza metoda do pobrania artyklow przyjmuje obiekt i wyszukuje po jego polach w bazie danych */
        /* JeĹĽeli nie przekazano nic w body request to zwracanie wszystkich artykulow */
        /* Jezeli ĹĽaden aartkul nie pasuje do parametrĂłw przekazanych w RequestBody lub nie ma nic w bazie to 404 */
        try {
            $requestArray = $request->toArray();
            $criteria = $requestArray['criteria'] ?? [];
            $orderBy = $requestArray['orderBy'] ?? [];
            $limit = $requestArray['limit'] ?? 50;
            $offset = $requestArray['offset'] ?? 0;
            /* Poprawka, like search powinno byc przekazane w requestArray a poprawnym przzepisaniem tych parametrow powinno zajac sie repozitory a*/
            $articles = $articleRepository->findByExtended($criteria, $orderBy, $limit, $offset);
            $total = $articleRepository->countByExtended($criteria);

        } catch (\Exception $exception) {
            if($exception->getMessage() == "Request body is empty.") {
                $articles = $articleRepository->findAll();
                $total = count($articles);
            }else{
                throw $exception;
            }
        }
        if(!$articles) return new JsonResponse(['message' => 'Nie znaleziono'], 404);
        
        // Pobierz wszystkie gĹ‚Ăłwne zdjÄ™cia w jednym zapytaniu
        $articleIds = array_map(fn($article) => $article->getId(), $articles);
        $mainImages = $imageRepository->findMainImagesForArticles($articleIds);
        
        $withThumbnails = [];
        foreach ($articles as $article) {
            $articleId = $article->getId();
            $mainImage = $mainImages[$articleId] ?? null;
            // PrzekaĹĽ articleId bezpoĹ›rednio, aby uniknÄ…Ä‡ problemĂłw z relacjami Doctrine
            $thumbnailUrl = $mainImage ? $imageUploadService->getThumbnailUrl($mainImage, $articleId) : null;
            
            // RÄ™cznie buduj tablicÄ™ z wĹ‚aĹ›ciwoĹ›ci Article
            $articleArray = [
                'id' => $article->id,
                'code' => $article->code,
                'ean13' => $article->ean13,
                'price' => $article->price,
                'id_category' => $article->id_category,
                'name' => $article->name,
                'description' => $article->description,
                'thumbnail_url' => $thumbnailUrl
            ];
            $withThumbnails[] = (object)$articleArray;
        }
        return new JsonResponse(['data' => $withThumbnails, 'total' => $total]);
    }

    /**
     * WyĹ›wietla konkretny artykuĹ‚
     */
    #[OA\Tag(name: "Article")]
    #[OA\Response(
        response: 200,
        description: "SzczegĂłĹ‚y artykuĹ‚u",
        content: new Model(type: ArticleDetailResponse::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono artykuĹ‚u"
    )]
    #[Route('/article/{id_article}', name: 'app_article_get_one', methods: ["GET"])]
    public function getOne(ArticleRepository $articleRepository, ArticleLanguageRepository $articleLanguageRepository, ArticleEanRepository $articleEanRepository, int $id_article): JsonResponse
    {
        $article = $articleRepository->findOneBy(['id' => $id_article]);
        if(!$article) return new JsonResponse(["message" => 'Nie znaleziono produktu'], 404);
        $translations = $articleLanguageRepository->findByArticleId($article->getId());
        $eanList = array_map(fn($e) => $e->getEan13(), $articleEanRepository->findByArticleId($article->getId()));
        $data = (object) array_merge( (array)$article, array( 'translations' => $translations, 'ean13_list' => $eanList ) );
        return new JsonResponse($data);
    }

    /**
     * Tworzy nowy artykuĹ‚
     */
    #[OA\Tag(name: "Article")]
    #[OA\RequestBody(
        description: "ArtykuĹ‚",
        required: true,
        content: new Model(type: ArticleCreateRequest::class)
    )]
    #[OA\Response(
        response: 200,
        description: "Stworzony artykuĹ‚",
        content: new Model(type: ArticleDetailResponse::class)
    )]
    #[OA\Response(
        response: 400,
        description: "ArtykuĹ‚ o podanym kodzie juz istnieje"
    )]
    #[Route('/article', name: 'app_article_create', methods: ["POST"])]
    public function create(ManagerRegistry $doctrine, LanguageRepository $languageRepository, ArticleRepository $articleRepository, ArticleLanguageRepository $articleLanguageRepository, ArticleEanRepository $articleEanRepository, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $languageManager = $doctrine->getManagerForClass(Language::class);
        $articleLanguageManager = $doctrine->getManagerForClass(ArticleLanguage::class);
        $articleEanManager = $doctrine->getManagerForClass(ArticleEan::class);
        $requestArray = $request->toArray();
        /* Sprawdzanie czy artykuĹ‚ o podanym kodzie nie istnieje juz w bazie danych */
        $articleResult = $articleRepository->findOneByCode($requestArray['code']);
        if($articleResult !== null) {
            return new JsonResponse(["message" => "ArtykuĹ‚ o takim kodzie juĹĽ istnieje"], 400);
        }
        /* Sprawdzanie czy wszystkie wymagane pola zostaĹ‚y przekazane */
        $requiredFields = ['code', 'price', 'id_category', 'name', 'description'];
        foreach ($requiredFields as $requiredField) {
            if (!isset($requestArray[$requiredField])) {
                return new JsonResponse(["message" => "Nie przekazano wymaganego parametru '" . $requiredField . "'"], 400);
            }
        }
        if (!isset($requestArray['ean13']) && !isset($requestArray['ean13_list'])) {
            return new JsonResponse(["message" => "Nie przekazano ĹĽadnego kodu EAN (ean13 lub ean13_list)"], 400);
        }

        /* Ustawianie podstawowych danych artykuĹ‚u */
        $article = new Article();
        $article->setCode($requestArray['code']);
        // Przygotowanie listy EAN
        $eanList = [];
        if (isset($requestArray['ean13_list']) && is_array($requestArray['ean13_list'])) {
            $eanList = $requestArray['ean13_list'];
        }
        if (isset($requestArray['ean13'])) {
            $eanList[] = $requestArray['ean13'];
        }
        $eanList = array_values(array_unique(array_values(array_filter(array_map('strval', $eanList), fn($v) => trim($v) !== ''))));
        if (count($eanList) === 0) {
            return new JsonResponse(["message" => "Lista kodĂłw EAN jest pusta"], 400);
        }
        $article->setEan13($eanList[0]);
        $article->setPrice($requestArray['price']);
        $article->setIdCategory($requestArray['id_category']);
        $article->setName($requestArray['name']);
        $article->setDescription($requestArray['description']);
        $entityManager->persist($article);
        $entityManager->flush();

        // Zapis listy EAN do tabeli article_ean
        foreach ($eanList as $ean) {
            $ae = new ArticleEan();
            $ae->setIdArticle($article->getId());
            $ae->setEan13($ean);
            $articleEanManager->persist($ae);
            $articleEanManager->flush();
        }

        /* Ustawianie tĹ‚umaczeĹ„ jezeli zotaly przekazane */
        if(isset($requestArray['translations'])) {
            foreach ($requestArray['translations'] as $translation) {
                /* Sprawdzanie czy jÄ™zyk o podanym id juĹĽ istnieje */
                $language = $languageRepository->findOneBy(['id' => $translation['id_language']]);
                if($language === null) {
                    return new JsonResponse(["message" => "Nie znaleziono jÄ™zyka o podanym id_language ".$translation['id_language']], 400);
                }
                $articleLanguage = new ArticleLanguage();
                $articleLanguage->setName($translation['name']);
                $articleLanguage->setDescription($translation['description']);
                $articleLanguage->setIdArticle($article->getId());
                $articleLanguage->setIdLanguage($translation['id_language']);
                $articleLanguageManager->persist($articleLanguage);
                $articleLanguageManager->flush();
            }
        }

        $data = (object)array_merge((array)$article, [
            "translations" => $articleLanguageRepository->findByArticleId($article->getId()),
            "ean13_list" => array_map(fn($e) => $e->getEan13(), $articleEanRepository->findByArticleId($article->getId()))
        ]);

        return new JsonResponse($data);
    }

    /**
     * Edytuje istniejÄ…cy artykuĹ‚
     *
     * JeĹĽeli chcesz dodaÄ‡ nowe tĹ‚umaczenie do artykuĹ‚u moĹĽesz rĂłwnieĹĽ skorzystaÄ‡ z tej metody. Poprostu w tabeli z tĹ‚umaczeniami przekaĹĽ jedno bez id, a zostanie ono automatycznie utworzone
     */
    #[OA\Tag(name: "Article")]
    #[OA\RequestBody(
        description: "ArtykuĹ‚",
        required: true,
        content: new Model(type: ArticleUpdateRequest::class)
    )]
    #[OA\Response(
        response: 200,
        description: "Zaktualizowany artykuĹ‚",
        content: new Model(type: ArticleDetailResponse::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono artykuĹ‚u o podanym id"
    )]
    #[Route('/article', name: 'app_article_edit', methods: ["PUT"])]
    public function edit(ManagerRegistry $doctrine, LanguageRepository $languageRepository, ArticleRepository $articleRepository, ArticleLanguageRepository $articleLanguageRepository, ArticleEanRepository $articleEanRepository, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $articleLanguageManager = $doctrine->getManagerForClass(ArticleLanguage::class);
        $articleEanManager = $doctrine->getManagerForClass(ArticleEan::class);
        $requestArray = $request->toArray();
        $article = $articleRepository->findOneBy(['id' => $requestArray['id']]);
        if($article === null) {
            return new JsonResponse(["message" => "Nie znaleziono artykuĹ‚u o podanym kodzie"], 404);
        }
        /* Ustawianie danych artykuĹ‚u */
        $article->setCode($requestArray['code']);
        $article->setPrice($requestArray['price']);
        $article->setIdCategory($requestArray['id_category']);
        $article->setName($requestArray['name']);
        $article->setDescription($requestArray['description']);
        $entityManager->persist($article);
        $entityManager->flush();

        // Synchronizacja listy EAN jeĹ›li przekazana
        if (isset($requestArray['ean13_list']) || isset($requestArray['ean13'])) {
            $eanList = [];
            if (isset($requestArray['ean13_list']) && is_array($requestArray['ean13_list'])) {
                $eanList = $requestArray['ean13_list'];
            }
            if (isset($requestArray['ean13'])) {
                $eanList[] = $requestArray['ean13'];
            }
            $eanList = array_values(array_unique(array_values(array_filter(array_map('strval', $eanList), fn($v) => trim($v) !== ''))));
            if (count($eanList) > 0) {
                // zaktualizuj gĹ‚Ăłwny ean
                $article->setEan13($eanList[0]);
                $entityManager->persist($article);
                $entityManager->flush();

                $existing = $articleEanRepository->findByArticleId($article->getId());
                $existingValues = array_map(fn($e) => $e->getEan13(), $existing);
                // usuĹ„ nieobecne
                foreach ($existing as $existingEan) {
                    if (!in_array($existingEan->getEan13(), $eanList, true)) {
                        $articleEanManager->remove($existingEan);
                        $articleEanManager->flush();
                    }
                }
                // dodaj nowe
                foreach ($eanList as $ean) {
                    if (!in_array($ean, $existingValues, true)) {
                        $ae = new ArticleEan();
                        $ae->setIdArticle($article->getId());
                        $ae->setEan13($ean);
                        $articleEanManager->persist($ae);
                        $articleEanManager->flush();
                    }
                }
            }
        }
        /* Ustawianie tĹ‚umaczeĹ„ jezeli zostaly przekazane */
        if(isset($requestArray['translations'])) {
            foreach ($requestArray['translations'] as $translation) {
                if(!isset($translation['id'])) {
                    /* Nie podano id istniejÄ…cego tĹ‚umaczenia wiÄ™c tworzymy nowe, uznajÄ…c ĹĽe uĹĽytkownik chce stworzyÄ‡ nowe tĹ‚umaczenie dla tego artykuĹ‚u */
                    $articleLanguage = new ArticleLanguage();
                    $articleLanguage->setIdArticle($article->getId());
                    $articleLanguage->setIdLanguage($translation['id_language']);
                }else{
                    $articleLanguage = $articleLanguageRepository->findOneBy(['id' => $translation['id']]);
                }
                $articleLanguage->setName($translation['name']);
                $articleLanguage->setDescription($translation['description']);
                $articleLanguageManager->persist($articleLanguage);
                $articleLanguageManager->flush();
            }
        }


        $data = (object)array_merge((array)$article, [
            "translations" => $articleLanguageRepository->findByArticleId($article->getId()),
            "ean13_list" => array_map(fn($e) => $e->getEan13(), $articleEanRepository->findByArticleId($article->getId()))
        ]);

        return new JsonResponse($data);
    }

    /**
     * Usuwa istniejÄ…cy artykuĹ‚
     */
    #[OA\Tag(name: "Article")]
    #[OA\Response(
        response: 200,
        description: "UsuniÄ™to",
        content: new Model(type: MessageResponse::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono artykuĹ‚u o podanym kodzie"
    )]
    #[Route('/article/{id_article}', name: 'app_article_delete', methods: ["DELETE"])]
    public function delete(ManagerRegistry $doctrine, LanguageRepository $languageRepository, ArticleRepository $articleRepository, ArticleLanguageRepository $articleLanguageRepository, ArticleEanRepository $articleEanRepository, int $id_article): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $articleLanguageManager = $doctrine->getManagerForClass(ArticleLanguage::class);
        $articleEanManager = $doctrine->getManagerForClass(ArticleEan::class);
        $article = $articleRepository->findOneBy(['id' => $id_article]);
        if($article === null) {
            return new JsonResponse(["message" => "Nie znaleziono artykuĹ‚u o podanym id"], 404);
        }
        /* Usuwanie tlumaczeĹ„ artykuĹ‚u */
        $translations = $articleLanguageRepository->findBy(['id_article' => $article->getId()]);
        foreach ($translations as $translation) {
            $articleLanguageManager->remove($translation);
            $articleLanguageManager->flush();
        }
        /* Usuwanie eanĂłw artykuĹ‚u */
        $eans = $articleEanRepository->findBy(['id_article' => $article->getId()]);
        foreach ($eans as $ean) {
            $articleEanManager->remove($ean);
            $articleEanManager->flush();
        }
        /* Usuwanie samego artykuĹ‚u */
        $entityManager->remove($article);
        $entityManager->flush();

        return new JsonResponse(["message" => "UsuniÄ™to"]);
    }

    private function deleteRelatedEntities(ManagerRegistry $doctrine, Article $article): void
    {
        $entityManager = $doctrine->getManager();
        $articleId = $article->getId();

        $repositories = [
            'App\Entity\ArticleLanguage' => 'id_article',
            'App\Entity\ArticleEan' => 'id_article',
            'App\Entity\ArticleCar' => 'id_article',
            'App\Entity\ArticleCategory' => 'id_article',
            'App\Entity\ArticleStock' => 'id_article',
        ];

        foreach ($repositories as $entityClass => $fieldName) {
            $repo = $doctrine->getRepository($entityClass);
            $items = $repo->findBy([$fieldName => $articleId]);
            foreach ($items as $item) {
                $entityManager->remove($item);
            }
        }
        $entityManager->flush();
    }
}
