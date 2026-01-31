<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\ArticleCar;
use App\Entity\Car;
use App\Entity\ImportJob;
use App\Entity\Setting;
use App\Entity\ImportRowsAffected;
use App\Repository\ArticleRepository;
use App\Repository\CarRepository;
use App\Repository\ImportJobRepository;
use App\Repository\ImportRowsAffectedRepository;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class ImportService
{
    private const BATCH_SIZE = 50;

    public function __construct(
        private CarRepository $carRepository,
        private ArticleRepository $articleRepository,
        private EntityManagerInterface $entityManager,
        private ImportJobRepository $importJobRepository,
        private ImportRowsAffectedRepository $importRowsAffectedRepository,
        private HubInterface $hub
    ) {
    }

    public function processJob(int $jobId): void
    {
        $job = $this->importJobRepository->find($jobId);
        if (!$job) {
            return;
        }

        if (in_array($job->getStatus(), [ImportJob::STATUS_CANCELLED, ImportJob::STATUS_COMPLETED, ImportJob::STATUS_REVERTED])) {
            return;
        }

        $job->setStatus(ImportJob::STATUS_PROCESSING);
        $this->entityManager->flush();

        $filePath = $job->getFilePath();
        if (!file_exists($filePath)) {
            $job->setStatus('failed');
            $this->entityManager->flush();
            return;
        }

        // Calculate total rows if not set
        if ($job->getTotalRows() === null) {
            $lineCount = 0;
            // Try using wc -l for performance on Linux
            $cmd = sprintf('wc -l %s', escapeshellarg($filePath));
            $output = null;
            $returnVar = null;
            exec($cmd, $output, $returnVar);

            if ($returnVar === 0 && isset($output[0])) {
                $lineCount = (int) trim(explode(' ', trim($output[0]))[0]);
            } else {
                // Fallback to PHP count
                $handle = fopen($filePath, "r");
                while (!feof($handle)) {
                    if (fgets($handle) !== false)
                        $lineCount++;
                }
                fclose($handle);
            }

            // Subtract header
            $job->setTotalRows(max(0, $lineCount - 1));
            $this->entityManager->flush();
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $job->setStatus('failed');
            $this->entityManager->flush();
            return;
        }

        // Detect delimiter? Assuming semi-colon or passed in mapping?
        // Mapping is stored in Job. Delimiter is NOT. We should store it or assume.
        // Let's assume ';' or detect.
        $delimiter = ',';
        // Simple detection override if needed or store in job entity next time.

        // Read header
        $header = fgetcsv($handle, 0, $delimiter);
        $headerOffset = ftell($handle);

        $resumeOffset = $job->getProcessedOffset();
        if ($resumeOffset > $headerOffset) {
            fseek($handle, $resumeOffset);
        }

        $mapping = $job->getMapping();
        $batch = [];
        $rowsProcessedInBatch = 0;

        // Stats - accumulated in Job, but we track delta here for batch

        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                // Skip empty lines
                if (array_filter($row) === []) {
                    continue;
                }

                if (count($row) === count($header)) {
                    $batch[] = array_combine($header, $row);
                    $rowsProcessedInBatch++;
                }

                if (count($batch) >= self::BATCH_SIZE) {
                    if ($job->getImportType() === 'cars') {
                        $this->importCars($batch, $mapping, $job->getArticleIdentifierField(), $job);
                    } else {
                        $this->processBatch($batch, $mapping, $job);
                    }
                    $batch = [];

                    // Update Job Progress - Offset only, Rows are updated inside sub-methods now
                    $currentOffset = ftell($handle);
                    $job->setProcessedOffset($currentOffset);

                    if ($this->entityManager->isOpen()) {
                        $this->entityManager->flush();
                    } else {
                        throw new \Exception("EntityManager is closed, stopping import job.");
                    }
                    $rowsProcessedInBatch = 0;

                    // Publish Update
                    $this->publishProgress($job);



                    // Check for pause/stop signal
                    if ($this->entityManager->isOpen()) {
                        $this->entityManager->refresh($job);
                    }
                    if (in_array($job->getStatus(), [ImportJob::STATUS_CANCELLING, ImportJob::STATUS_CANCELLED])) {
                        $job->setStatus(ImportJob::STATUS_CANCELLED);
                        if ($this->entityManager->isOpen()) {
                            $this->entityManager->flush();
                        }
                        break;
                    }

                    if (in_array($job->getStatus(), [ImportJob::STATUS_PAUSING, ImportJob::STATUS_PAUSED])) {
                        $job->setStatus(ImportJob::STATUS_PAUSED);
                        if ($this->entityManager->isOpen()) {
                            $this->entityManager->flush();
                        }
                        break;
                    }
                }
            }

            // Process remaining
            if (count($batch) > 0) {
                if ($job->getImportType() === 'cars') {
                    $this->importCars($batch, $mapping, $job->getArticleIdentifierField(), $job);
                } else {
                    $this->processBatch($batch, $mapping, $job);
                }
            }

            if ($this->entityManager->isOpen()) {
                if (!in_array($job->getStatus(), [ImportJob::STATUS_CANCELLED, ImportJob::STATUS_PAUSED])) {
                    $job->setStatus(ImportJob::STATUS_COMPLETED);
                    $job->setProcessedOffset(ftell($handle));
                }
                $this->entityManager->flush();
            }
            $this->publishProgress($job);

        } catch (\Exception $e) {
            if ($this->entityManager->isOpen()) {
                $job->setStatus('failed');
                // Store error in job?
                $this->entityManager->flush();
            }
            $this->publishProgress($job);
            throw $e;
        } finally {
            fclose($handle);
        }
    }

    private function publishProgress(ImportJob $job): void
    {
        try {
            $update = new Update(
                'https://motolinker.local/import/progress/' . $job->getId(),
                json_encode([
                    'status' => $job->getStatus(),
                    'processed' => $job->getProcessedRows(),
                    'total' => $job->getTotalRows()
                ])
            );
            $this->hub->publish($update);
        } catch (\Exception $e) {
            // Ignore Mercure errors to not interrupt the import process
            // Log error if logger is available
        }
    }

    // Renamed existing processImport to processBatch for clarity, but logic remains similar
    // We pass $job to allow logging errors if we want, or just return stats.
    public function processBatch(array $mappedData, array $columnMapping, ?ImportJob $job = null): array
    {
        $stats = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => []
        ];

        $lastTimeUpdated = time();
        $lastProcessedCount = 0;
        $settingRepository = $this->entityManager->getRepository(Setting::class);

        foreach ($mappedData as $index => $row) {
            if ($job && $job->getDebugDelay() !== null && $job->getDebugDelay() > 0) {
                usleep($job->getDebugDelay() * 1000);
            }
            $stats['processed']++;

            // Throttled progress update
            if ($job) {
                try {
                    $flushInterval = (int) $settingRepository->getSetting('flush_interval') ?: 1;
                } catch (\Exception $e) {
                    $flushInterval = 1;
                }

                if (time() - $lastTimeUpdated >= $flushInterval) {
                    $delta = $stats['processed'] - $lastProcessedCount;
                    $job->setProcessedRows($job->getProcessedRows() + $delta);
                    $this->entityManager->flush();
                    $this->publishProgress($job);
                    $lastTimeUpdated = time();
                    $lastProcessedCount = $stats['processed'];
                }
            }

            try {
                // Check required fields
                // Assuming 'code' is the unique identifier for Article
                $codeColumn = array_search('code', $columnMapping);
                if ($codeColumn === false || !isset($row[$codeColumn])) {
                    throw new \Exception("Missing Article Code");
                }

                $articleCode = $row[$codeColumn];

                // Update or Create Article
                $article = $this->articleRepository->findOneBy(['code' => $articleCode]);
                if (!$article) {
                    $article = new Article();
                    $article->setCode($articleCode);
                    $stats['created']++;
                    $this->entityManager->persist($article);
                    $this->entityManager->flush();
                    $this->logAffectedRow($job, 'articles', $article->getId());
                } else {
                    $stats['updated']++;
                }

                // Map other properties
                foreach ($columnMapping as $csvCol => $entityField) {
                    if ($entityField === 'zastosowania') {
                        continue; // Process later
                    }
                    if (isset($row[$csvCol])) {
                        $value = $row[$csvCol];
                        $setter = 'set' . str_replace('_', '', ucwords($entityField, '_'));

                        // Handle specific type conversions if needed
                        if ($entityField === 'id_category') {
                            $value = (int) $value;
                        }
                        if ($entityField === 'price') {
                            $value = (float) $value;
                        }

                        if (method_exists($article, $setter)) {
                            $article->$setter($value);
                        }
                    }
                }

                $this->entityManager->persist($article);

                // Process "zastosowania"
                $zastosowaniaCol = array_search('zastosowania', $columnMapping);
                if ($zastosowaniaCol !== false && isset($row[$zastosowaniaCol])) {
                    $carErrors = $this->processArticleCars($article, $row[$zastosowaniaCol], $job);
                    foreach ($carErrors as $ce) {
                        $stats['errors'][] = "Row $index (Car): " . $ce;
                    }
                }

            } catch (\Exception $e) {
                $errorMsg = "Row $index: " . $e->getMessage();
                $stats['errors'][] = $errorMsg;
                // Log error to job if needed?

                if (!$this->entityManager->isOpen()) {
                    $msg = "CRITICAL: Database connection closed due to error: " . $e->getMessage();
                    $stats['errors'][] = $msg;
                    throw new \Exception($msg, 0, $e);
                }
            }
        }

        if ($job) {
            $delta = $stats['processed'] - $lastProcessedCount;
            if ($delta > 0) {
                $job->setProcessedRows($job->getProcessedRows() + $delta);
            }
        }

        if ($this->entityManager->isOpen()) {
            $this->entityManager->flush();
            $this->entityManager->clear(); // Detach objects to save memory
        }
        return $stats;
    }

    public function parseCsvContent(string $content, string $delimiter = ';'): array
    {
        $lines = explode("\n", $content);
        $data = [];
        $header = null;

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            $row = str_getcsv($line, $delimiter);

            if (!$header) {
                $header = $row;
            } else {
                if (count($row) === count($header)) {
                    $data[] = array_combine($header, $row);
                }
            }
        }

        return ['header' => $header, 'data' => $data];
    }

    public function getCsvHeaders(string $content, string $delimiter = ';'): array
    {
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (!empty(trim($line))) {
                // Check for BOM
                $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
                return str_getcsv($line, $delimiter);
            }
        }
        return [];
    }

    // Kept for backward compatibility with existing controller methods if any
    public function processImport(array $mappedData, array $columnMapping): array
    {
        return $this->processBatch($mappedData, $columnMapping);
    }

    public function importArticles(array $mappedData, array $columnMapping): array
    {
        $stats = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => []
        ];

        foreach ($mappedData as $index => $row) {
            $stats['processed']++;

            try {
                // Check required fields
                $codeColumn = array_search('code', $columnMapping);
                if ($codeColumn === false || !isset($row[$codeColumn])) {
                    throw new \Exception("Missing Article Code");
                }

                $articleCode = $row[$codeColumn];

                // Update or Create Article
                $article = $this->articleRepository->findOneBy(['code' => $articleCode]);
                if (!$article) {
                    $article = new Article();
                    $article->setCode($articleCode);
                    $stats['created']++;
                    $this->entityManager->persist($article);
                    $this->entityManager->flush();
                } else {
                    $stats['updated']++;
                }

                // Map other properties
                foreach ($columnMapping as $csvCol => $entityField) {
                    if ($entityField === 'zastosowania') {
                        continue;
                    }
                    if (isset($row[$csvCol])) {
                        $value = $row[$csvCol];
                        $setter = 'set' . str_replace('_', '', ucwords($entityField, '_'));

                        // Handle specific type conversions
                        if ($entityField === 'id_category') {
                            $value = (int) $value;
                        }
                        if ($entityField === 'price') {
                            $value = (float) $value;
                        }

                        if (method_exists($article, $setter)) {
                            $article->$setter($value);
                        }
                    }
                }

                $this->entityManager->persist($article);

            } catch (\Exception $e) {
                $stats['errors'][] = "Row $index: " . $e->getMessage();

                if (!$this->entityManager->isOpen()) {
                    $stats['errors'][] = "CRITICAL: Database connection closed due to error. Stopping import.";
                    break;
                }
            }
        }

        if ($this->entityManager->isOpen()) {
            $this->entityManager->flush();
        }
        return $stats;
    }

    public function importCars(array $mappedData, array $columnMapping, ?string $articleIdentifierField = 'articleCode', ?ImportJob $job = null): array
    {
        $stats = [
            'processed' => 0,
            'created_cars' => 0,
            'existing_cars' => 0,
            'linked_articles' => 0,
            'errors' => []
        ];

        $lastTimeUpdated = time();
        $lastProcessedCount = 0;
        $settingRepository = $this->entityManager->getRepository(Setting::class);

        foreach ($mappedData as $index => $row) {
            if ($job && $job->getDebugDelay() !== null && $job->getDebugDelay() > 0) {
                usleep($job->getDebugDelay() * 1000);
            }
            $stats['processed']++;

            try {
                // Create a transient Car object to calculate hash
                $car = new Car();
                $hasCarData = false;

                // Map Car properties
                // We assume columnMapping maps CSV Header => Car Entity Field
                foreach ($columnMapping as $csvCol => $entityField) {
                    // Skip if this field is intended for Article identification
                    if ($entityField === $articleIdentifierField) {
                        continue;
                    }

                    if (isset($row[$csvCol])) {
                        $value = $row[$csvCol];
                        // Basic empty check?

                        $setter = 'set' . str_replace('_', '', ucwords($entityField, '_'));

                        // Type conversions for Car
                        if ($entityField === 'cylinders') {
                            $value = (int) $value;
                        }
                        if ($entityField === 'model_to' && ($value === '' || $value === 'null' || $value === 'NULL')) {
                            $value = null;
                        }

                        if (method_exists($car, $setter)) {
                            $car->$setter($value);
                            $hasCarData = true;
                        }
                    }
                }

                if (!$hasCarData) {
                    // Skip empty rows if needed
                }

                $validationErrors = $this->validateCarData($car);
                if (!empty($validationErrors)) {
                    $stats['errors'][] = "Row $index skipped: " . implode(', ', $validationErrors);
                    continue;
                }

                $hash = $car->calculateHash();
                $car->setHash($hash);

                $existingCar = $this->carRepository->findOneBy(['hash' => $hash]);

                if (!$existingCar) {
                    $this->entityManager->persist($car);
                    $this->entityManager->flush();
                    $existingCar = $car;
                    $stats['created_cars']++;
                    $this->logAffectedRow($job, 'cars', $existingCar->getId());
                } else {
                    $stats['existing_cars']++;
                }

                // Link to Article if identifier is present
                if ($articleIdentifierField) {
                    // The user specifies which ENTITY FIELD in the mapping corresponds to the article connection.
                    // But usually mapping is Header => EntityField.
                    // The requirement says: "użytkownik ma mieć możliwośc wskazania które pole z tabeli articles ma służyć do połączenia domyślnie articleCode"
                    // This means the user says: "Column X in CSV holds the Value Y which matches Article.Field Z"
                    // Wait, the prompt says: "wskazania które pole z tabeli articles ma służyć do połączenia domyślnie articleCode"
                    // So we need to know:
                    // 1. Which Column in CSV has the identifier? -> We can infer this if we know the mapping.
                    //    Or we expect the mapping to contain an entry like "CSV_COL_NAME" => "article_code_reference" ?
                    //    The previous mapping was "CSV_HEADER" => "ENTITY_FIELD".
                    //    So if we want to link, we need to map a CSV header to something that identifies an article.
                    //    Let's assume we look for the key in mapping where value == $articleIdentifierField.

                    $csvColForArticle = array_search($articleIdentifierField, $columnMapping);

                    if ($csvColForArticle !== false && isset($row[$csvColForArticle])) {
                        $articleVal = $row[$csvColForArticle];

                        // Now find article by... usually 'code'.
                        // If $articleIdentifierField is 'code', we search by code.
                        // But what if the user wants to search by 'ean'? 
                        // The prompt implies we can choose the field in Article table.

                        // So we search Article where $articleIdentifierField = $articleVal
                        // BE CAREFUL: $articleIdentifierField in mapping might be 'article_code', but in Entity it is 'code'.

                        // Let's assume the user passes the entity field name as $articleIdentifierField. Default 'code'.

                        $article = $this->articleRepository->findOneBy([$articleIdentifierField => $articleVal]);

                        if ($article) {
                            // Check link
                            $existingLink = $this->entityManager->getRepository(ArticleCar::class)->findOneBy([
                                'id_article' => $article->getId(),
                                'id_car' => $existingCar->getId()
                            ]);

                            if (!$existingLink) {
                                $articleCar = new ArticleCar();
                                $articleCar->setIdArticle($article->getId());
                                $articleCar->setIdCar($existingCar->getId());
                                $this->entityManager->persist($articleCar);
                                $this->entityManager->flush();
                                $stats['linked_articles']++;
                                $this->logAffectedRow($job, 'article_car', $articleCar->getId());
                            }
                        }
                    }
                }

                if ($job) {
                    try {
                        $flushInterval = (int) $settingRepository->getSetting('flush_interval') ?: 1;
                    } catch (\Exception $e) {
                        $flushInterval = 1;
                    }

                    if (time() - $lastTimeUpdated >= $flushInterval) {
                        $delta = $stats['processed'] - $lastProcessedCount;
                        $job->setProcessedRows($job->getProcessedRows() + $delta);
                        $this->entityManager->flush();
                        $this->publishProgress($job);
                        $lastTimeUpdated = time();
                        $lastProcessedCount = $stats['processed'];
                    }
                }

            } catch (\Exception $e) {
                $stats['errors'][] = "Row $index: " . $e->getMessage();

                if (!$this->entityManager->isOpen()) {
                    $msg = "CRITICAL: Database connection closed due to error: " . $e->getMessage();
                    $stats['errors'][] = $msg;
                    throw new \Exception($msg, 0, $e);
                }
            }
        }

        if ($job) {
            $delta = $stats['processed'] - $lastProcessedCount;
            if ($delta > 0) {
                $job->setProcessedRows($job->getProcessedRows() + $delta);
            }
        }

        if ($this->entityManager->isOpen()) {
            $this->entityManager->flush();
        }

        return $stats;
    }

    private function processArticleCars(Article $article, string $zastosowaniaValue, ?ImportJob $job = null): array
    {
        $errors = [];
        // Detect delimiter for nested CSV
        $subDelimiter = ',';
        if (strpos($zastosowaniaValue, '|') !== false) {
            $subDelimiter = '|';
        } elseif (strpos($zastosowaniaValue, ';') !== false) {
            $subDelimiter = ';';
        }

        $lines = explode("\n", trim($zastosowaniaValue));
        if (empty($lines)) {
            return $errors;
        }

        // Assume first line is header
        $headerLine = array_shift($lines);
        $headers = str_getcsv($headerLine, $subDelimiter);

        foreach ($lines as $lineIndex => $line) {
            if (empty(trim($line))) {
                continue;
            }
            $row = str_getcsv($line, $subDelimiter);

            if (count($row) !== count($headers)) {
                $errors[] = "Embedded CSV line $lineIndex: Column count mismatch";
                continue; // Skip malformed rows
            }

            $carData = array_combine($headers, $row);

            // Create a transient Car object to calculate hash
            $car = new Car();

            // Map known fields. We try to map keys from CSV to setters.
            $properties = [
                'manufacturer',
                'model',
                'type',
                'model_from',
                'model_to',
                'body_type',
                'drive_type',
                'displacement_liters',
                'displacement_cmm',
                'fuel_type',
                'kw',
                'hp',
                'cylinders',
                'valves',
                'engine_type',
                'engine_codes',
                'kba',
                'text_value'
            ];

            foreach ($properties as $prop) {
                // Try exact match or snake_case
                $value = $carData[$prop] ?? null;
                if ($value === null) {
                    // Try to find case-insensitive key
                    $key = $this->findKeyCaseInsensitive($carData, $prop);
                    if ($key) {
                        $value = $carData[$key];
                    }
                }

                if ($value !== null) {
                    // Handle types
                    if ($prop === 'cylinders') {
                        $value = (int) $value;
                    }
                    if ($prop === 'model_to' && ($value === '' || $value === 'null' || $value === 'NULL')) {
                        $value = null;
                    }

                    $setter = 'set' . str_replace('_', '', ucwords($prop, '_'));
                    if (method_exists($car, $setter)) {
                        $car->$setter($value);
                    }
                }
            }

            // Validate before calculate Hash or Persist?
            // Hash relies on data. Persist relies on validity.

            $validationErrors = $this->validateCarData($car);
            if (!empty($validationErrors)) {
                $errors[] = "Embedded CSV line $lineIndex: Invalid car data: " . implode(', ', $validationErrors);
                continue;
            }

            $hash = $car->calculateHash();
            $car->setHash($hash);

            // Find existing Car
            $existingCar = $this->carRepository->findOneBy(['hash' => $hash]);

            if (!$existingCar) {
                // Create new Car if not found
                $this->entityManager->persist($car);
                $this->entityManager->flush(); // Flush to get ID for the new car
                $existingCar = $car;
                $this->logAffectedRow($job, 'cars', $existingCar->getId());
            }

            // Link Article to Car
            if ($existingCar) {
                // Check if link exists
                $existingLink = $this->entityManager->getRepository(ArticleCar::class)->findOneBy([
                    'id_article' => $article->getId(),
                    'id_car' => $existingCar->getId()
                ]);

                if (!$existingLink) {
                    $articleCar = new ArticleCar();
                    $articleCar->setIdArticle($article->getId());
                    $articleCar->setIdCar($existingCar->getId());
                    $this->entityManager->persist($articleCar);
                    $this->entityManager->flush();
                    $this->logAffectedRow($job, 'article_car', $articleCar->getId());
                }
            }
        }

        return $errors;
    }

    private function logAffectedRow(?ImportJob $job, string $table, int $rowId): void
    {
        if (!$job) {
            return;
        }

        $settingRepository = $this->entityManager->getRepository(Setting::class);
        $logEnabled = $settingRepository->getSetting('log_for_reverting') === 'true';

        if (!$logEnabled) {
            return;
        }

        $affected = new ImportRowsAffected();
        $affected->setJobId($job->getId());
        $affected->setTable($table);
        $affected->setRowId($rowId);
        // userId can be added if we have current user context, for now leave null

        $this->entityManager->persist($affected);
        // We might want to flush this periodically or at least once per batch, 
        // but since we need to ensure it's saved even if the import crashes later, 
        // flushing here is safer but slower. 
        // Given the performance concern, maybe we should batch these?
        // Let's flush here for simplicity for now.
        $this->entityManager->flush();
    }

    public function revertJob(int $jobId): void
    {
        $job = $this->importJobRepository->find($jobId);
        if (!$job) {
            throw new \Exception("Job not found");
        }

        $job->setStatus(ImportJob::STATUS_REVERTING);
        $this->entityManager->flush();

        $affectedRows = $this->importRowsAffectedRepository->findByJobId($jobId);

        // Delete in order: article_car links first, then articles and cars
        // Actually findByJobId returns them in DESC order of ID, which is likely perfect (LIFO delete)
        foreach ($affectedRows as $affected) {
            $tableName = $affected->getTable();
            $rowId = $affected->getRowId();

            try {
                if ($tableName === 'article_car') {
                    $entity = $this->entityManager->getRepository(ArticleCar::class)->find($rowId);
                } elseif ($tableName === 'articles') {
                    $entity = $this->entityManager->getRepository(Article::class)->find($rowId);
                } elseif ($tableName === 'cars') {
                    $entity = $this->entityManager->getRepository(Car::class)->find($rowId);
                } else {
                    continue;
                }

                if ($entity) {
                    $this->entityManager->remove($entity);
                }
            } catch (\Exception $e) {
                // Log error or ignore if already gone
            }
        }

        $this->entityManager->flush();

        // Also delete the log entries?
        foreach ($affectedRows as $affected) {
            $this->entityManager->remove($affected);
        }

        $job->setStatus(ImportJob::STATUS_REVERTED);
        $this->entityManager->flush();
    }

    private function findKeyCaseInsensitive(array $array, string $key): ?string
    {
        foreach (array_keys($array) as $k) {
            if (strtolower($k) === strtolower($key)) {
                return $k;
            }
        }
        return null;
    }

    private function validateCarData(Car $car): array
    {
        $errors = [];
        // Check required fields based on Entity definition (not nullable)
        if (!$car->getManufacturer())
            $errors[] = "Missing manufacturer";
        if (!$car->getModel())
            $errors[] = "Missing model";
        if (!$car->getType())
            $errors[] = "Missing type";
        // Add other required fields if strictly needed, or maybe be lenient?
        // User said: "Column 'manufacturer' cannot be null"

        return $errors;
    }
}
