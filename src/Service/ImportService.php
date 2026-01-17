<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\ArticleCar;
use App\Entity\Car;
use App\Repository\ArticleRepository;
use App\Repository\CarRepository;
use Doctrine\ORM\EntityManagerInterface;

class ImportService
{
    public function __construct(
        private CarRepository $carRepository,
        private ArticleRepository $articleRepository,
        private EntityManagerInterface $entityManager
    ) {
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
                return str_getcsv($line, $delimiter);
            }
        }
        return [];
    }

    public function processImport(array $mappedData, array $columnMapping): array
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
                    $this->processArticleCars($article, $row[$zastosowaniaCol]);
                }

            } catch (\Exception $e) {
                $stats['errors'][] = "Row $index: " . $e->getMessage();
            }
        }

        $this->entityManager->flush();
        return $stats;
    }

    private function processArticleCars(Article $article, string $zastosowaniaValue): void
    {
        // Detect delimiter for nested CSV
        $subDelimiter = ',';
        if (strpos($zastosowaniaValue, '|') !== false) {
            $subDelimiter = '|';
        } elseif (strpos($zastosowaniaValue, ';') !== false) {
            $subDelimiter = ';';
        }

        $lines = explode("\n", trim($zastosowaniaValue));
        if (empty($lines)) {
            return;
        }

        // Assume first line is header
        $headerLine = array_shift($lines);
        $headers = str_getcsv($headerLine, $subDelimiter);

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            $row = str_getcsv($line, $subDelimiter);

            if (count($row) !== count($headers)) {
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

            $hash = $car->calculateHash();

            // Find existing Car
            $existingCar = $this->carRepository->findOneBy(['hash' => $hash]);

            if ($existingCar) {
                // Link Article to Car
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
}
