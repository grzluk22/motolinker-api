<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\ArticleCar;
use App\Entity\Car;
use App\Entity\ImportJob;
use App\Repository\ArticleRepository;
use App\Repository\CarRepository;
use App\Repository\ImportJobRepository;
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
        private HubInterface $hub
    ) {
    }

    public function processJob(int $jobId): void
    {
        $job = $this->importJobRepository->find($jobId);
        if (!$job) {
            return;
        }

        $job->setStatus('processing');
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
        $delimiter = ';';
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
                        $this->importCars($batch, $mapping, $job->getArticleIdentifierField());
                    } else {
                        $this->processBatch($batch, $mapping, $job);
                    }
                    $batch = [];

                    // Update Job Progress
                    $currentOffset = ftell($handle);
                    $job->setProcessedOffset($currentOffset);
                    $job->setProcessedRows($job->getProcessedRows() + $rowsProcessedInBatch);

                    if ($this->entityManager->isOpen()) {
                        $this->entityManager->flush();
                    } else {
                        throw new \Exception("EntityManager is closed, stopping import job.");
                    }
                    $rowsProcessedInBatch = 0;

                    // Publish Update
                    $this->publishProgress($job);

                    // Debug delay for testing progress tracking
                    if ($job->getDebugDelay() !== null && $job->getDebugDelay() > 0) {
                        usleep($job->getDebugDelay() * 1000); // Convert milliseconds to microseconds
                    }

                    // Check for pause/stop signal?
                    // Reload job to check status if changed by API?
                    if ($this->entityManager->isOpen()) {
                        $this->entityManager->refresh($job);
                    }
                    if ($job->getStatus() === 'cancelling') {
                        $job->setStatus('cancelled');
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
                    $this->importCars($batch, $mapping, $job->getArticleIdentifierField());
                } else {
                    $this->processBatch($batch, $mapping, $job);
                }
                $job->setProcessedRows($job->getProcessedRows() + $rowsProcessedInBatch);
            }

            if ($this->entityManager->isOpen()) {
                if ($job->getStatus() !== 'cancelled') {
                    $job->setStatus('completed');
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

        foreach ($mappedData as $index => $row) {
            $stats['processed']++;

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
                    $carErrors = $this->processArticleCars($article, $row[$zastosowaniaCol]);
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

    public function importCars(array $mappedData, array $columnMapping, ?string $articleIdentifierField = 'articleCode'): array
    {
        $stats = [
            'processed' => 0,
            'created_cars' => 0,
            'existing_cars' => 0,
            'linked_articles' => 0,
            'errors' => []
        ];

        foreach ($mappedData as $index => $row) {
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
                                $stats['linked_articles']++;
                            }
                        }
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

        if ($this->entityManager->isOpen()) {
            $this->entityManager->flush();
        }

        return $stats;
    }

    private function processArticleCars(Article $article, string $zastosowaniaValue): array
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
                }
            }
        }

        return $errors;
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
