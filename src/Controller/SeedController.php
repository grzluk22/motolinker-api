<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\CategoryLanguage;
use App\Entity\Car;
use App\Entity\Criterion;
use App\Entity\CriterionLanguage;
use App\Entity\Language;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\CarRepository;
use App\Repository\CriterionRepository;
use App\Repository\LanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class SeedController extends AbstractController
{
    /**
     * Inicjuje bazę danych przykładowymi rekordami.
     *
     * @OA\Post(
     *     path="/seed/sample-data",
     *     summary="Uzupełnia bazę danymi demonstracyjnymi",
     *     tags={"Seed"},
     *     @OA\Response(
     *         response=201,
     *         description="Dane zostały dodane"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Baza danych zawiera już dane wymaganych typów"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Wystąpił błąd podczas inicjalizacji danych"
     *     )
     * )
     */
    #[Route('/seed/sample-data', name: 'app_seed_sample_data', methods: ['POST'])]
    public function seedSampleData(
        EntityManagerInterface $entityManager,
        LanguageRepository $languageRepository,
        CategoryRepository $categoryRepository,
        CriterionRepository $criterionRepository,
        CarRepository $carRepository,
        ArticleRepository $articleRepository
    ): JsonResponse {
        $existingData = [
            'languages' => $languageRepository->count([]),
            'categories' => $categoryRepository->count([]),
            'criteria' => $criterionRepository->count([]),
            'cars' => $carRepository->count([]),
            'articles' => $articleRepository->count([])
        ];

        foreach ($existingData as $type => $count) {
            if ($count > 0) {
                return new JsonResponse([
                    'message' => sprintf(
                        'Tabela %s nie jest pusta. Przed ponownym zasianiem wyczyść bazę danych.',
                        $type
                    )
                ], 409);
            }
        }

        $connection = $entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $summary = [
                'languages' => 0,
                'categories' => 0,
                'category_translations' => 0,
                'criteria' => 0,
                'criterion_translations' => 0,
                'cars' => 0,
                'articles' => 0
            ];

            $languageMap = [];
            $languagesData = [
                ['name' => 'Polski', 'isoCode' => 'pl'],
                ['name' => 'English', 'isoCode' => 'en'],
                ['name' => 'Deutsch', 'isoCode' => 'de']
            ];
            foreach ($languagesData as $languageData) {
                $language = new Language();
                $language->setName($languageData['name']);
                $language->setIsoCode($languageData['isoCode']);
                $entityManager->persist($language);
                $languageMap[$languageData['isoCode']] = $language;
                ++$summary['languages'];
            }
            $entityManager->flush();

            $categoryMap = [];
            $categoriesData = [
                'brakes' => [
                    'id_parent' => 0,
                    'translations' => [
                        [
                            'isoCode' => 'pl',
                            'name' => 'Układ hamulcowy',
                            'description' => 'Elementy eksploatacyjne układu hamulcowego.'
                        ],
                        [
                            'isoCode' => 'en',
                            'name' => 'Braking system',
                            'description' => 'Consumables for the braking system.'
                        ]
                    ]
                ],
                'suspension' => [
                    'id_parent' => 0,
                    'translations' => [
                        [
                            'isoCode' => 'pl',
                            'name' => 'Zawieszenie',
                            'description' => 'Części układu zawieszenia i amortyzacji.'
                        ],
                        [
                            'isoCode' => 'en',
                            'name' => 'Suspension',
                            'description' => 'Parts for suspension and damping systems.'
                        ]
                    ]
                ],
                'engine' => [
                    'id_parent' => 0,
                    'translations' => [
                        [
                            'isoCode' => 'pl',
                            'name' => 'Silnik',
                            'description' => 'Podzespoły oraz akcesoria silnikowe.'
                        ],
                        [
                            'isoCode' => 'en',
                            'name' => 'Engine',
                            'description' => 'Components and accessories for engines.'
                        ]
                    ]
                ]
            ];

            foreach ($categoriesData as $key => $categoryData) {
                $category = new Category();
                $category->setIdParent($categoryData['id_parent']);
                $entityManager->persist($category);
                $categoryMap[$key] = $category;
                ++$summary['categories'];
            }
            $entityManager->flush();

            foreach ($categoriesData as $key => $categoryData) {
                foreach ($categoryData['translations'] as $translationData) {
                    $language = $languageMap[$translationData['isoCode']] ?? null;
                    if ($language === null) {
                        throw new \RuntimeException(sprintf(
                            'Nie znaleziono języka o kodzie %s dla tłumaczenia kategorii %s.',
                            $translationData['isoCode'],
                            $key
                        ));
                    }
                    $categoryLanguage = new CategoryLanguage();
                    $categoryLanguage->setIdCategory($categoryMap[$key]->getId());
                    $categoryLanguage->setIdLanguage($language->getId());
                    $categoryLanguage->setName($translationData['name']);
                    $categoryLanguage->setDescription($translationData['description']);
                    $entityManager->persist($categoryLanguage);
                    ++$summary['category_translations'];
                }
            }
            $entityManager->flush();

            $criterionMap = [];
            $criteriaData = [
                'mounting_side' => [
                    'translations' => [
                        ['isoCode' => 'pl', 'name' => 'Strona montażu'],
                        ['isoCode' => 'en', 'name' => 'Mounting side']
                    ]
                ],
                'axle' => [
                    'translations' => [
                        ['isoCode' => 'pl', 'name' => 'Oś pojazdu'],
                        ['isoCode' => 'en', 'name' => 'Vehicle axle']
                    ]
                ]
            ];

            foreach ($criteriaData as $key => $criterionData) {
                $criterion = new Criterion();
                $entityManager->persist($criterion);
                $criterionMap[$key] = $criterion;
                ++$summary['criteria'];
            }
            $entityManager->flush();

            foreach ($criteriaData as $key => $criterionData) {
                foreach ($criterionData['translations'] as $translationData) {
                    $language = $languageMap[$translationData['isoCode']] ?? null;
                    if ($language === null) {
                        throw new \RuntimeException(sprintf(
                            'Nie znaleziono języka o kodzie %s dla tłumaczenia kryterium %s.',
                            $translationData['isoCode'],
                            $key
                        ));
                    }
                    $criterionLanguage = new CriterionLanguage();
                    $criterionLanguage->setIdCriterion($criterionMap[$key]->getId());
                    $criterionLanguage->setIdLanguage($language->getId());
                    $criterionLanguage->setName($translationData['name']);
                    $entityManager->persist($criterionLanguage);
                    ++$summary['criterion_translations'];
                }
            }
            $entityManager->flush();

            $carsData = [
                [
                    'manufacturer' => 'Audi',
                    'model' => 'A4',
                    'type' => 'B8',
                    'model_from' => '2007',
                    'model_to' => '2015',
                    'body_type' => 'Sedan',
                    'drive_type' => 'FWD',
                    'displacement_liters' => '2.0',
                    'displacement_cmm' => '1984',
                    'fuel_type' => 'Petrol',
                    'kw' => '165',
                    'hp' => '225',
                    'cylinders' => 4,
                    'valves' => '16',
                    'engine_type' => 'TFSI',
                    'engine_codes' => 'CDNC',
                    'kba' => '0588/ASD',
                    'text_value' => 'Audi A4 B8 2.0 TFSI'
                ],
                [
                    'manufacturer' => 'Volkswagen',
                    'model' => 'Golf',
                    'type' => 'VII',
                    'model_from' => '2012',
                    'model_to' => '2020',
                    'body_type' => 'Hatchback',
                    'drive_type' => 'FWD',
                    'displacement_liters' => '1.6',
                    'displacement_cmm' => '1598',
                    'fuel_type' => 'Diesel',
                    'kw' => '81',
                    'hp' => '110',
                    'cylinders' => 4,
                    'valves' => '16',
                    'engine_type' => 'TDI',
                    'engine_codes' => 'CLHA',
                    'kba' => '0603/BVO',
                    'text_value' => 'VW Golf VII 1.6 TDI'
                ]
            ];

            foreach ($carsData as $carData) {
                $car = new Car();
                $car->setManufacturer($carData['manufacturer']);
                $car->setModel($carData['model']);
                $car->setType($carData['type']);
                $car->setModelFrom($carData['model_from']);
                $car->setModelTo($carData['model_to']);
                $car->setBodyType($carData['body_type']);
                $car->setDriveType($carData['drive_type']);
                $car->setDisplacementLiters($carData['displacement_liters']);
                $car->setDisplacementCmm($carData['displacement_cmm']);
                $car->setFuelType($carData['fuel_type']);
                $car->setKw($carData['kw']);
                $car->setHp($carData['hp']);
                $car->setCylinders($carData['cylinders']);
                $car->setValves($carData['valves']);
                $car->setEngineType($carData['engine_type']);
                $car->setEngineCodes($carData['engine_codes']);
                $car->setKba($carData['kba']);
                $car->setTextValue($carData['text_value']);
                $entityManager->persist($car);
                ++$summary['cars'];
            }
            $entityManager->flush();

            $articlesData = [
                [
                    'code' => 'BRK-001',
                    'ean13' => '5901234567890',
                    'price' => 249.99,
                    'categoryKey' => 'brakes',
                    'name' => 'Zestaw klocków hamulcowych',
                    'description' => 'Komplet klocków hamulcowych przód do Audi A4 B8.',
                ],
                [
                    'code' => 'SUS-101',
                    'ean13' => '5901234567891',
                    'price' => 499.50,
                    'categoryKey' => 'suspension',
                    'name' => 'Amortyzator przedni',
                    'description' => 'Gazowy amortyzator przedni kompatybilny z VW Golf VII.',
                ],
                [
                    'code' => 'ENG-550',
                    'ean13' => '5901234567892',
                    'price' => 89.99,
                    'categoryKey' => 'engine',
                    'name' => 'Filtr oleju',
                    'description' => 'Filtr oleju 1.6 TDI z wkładem papierowym.',
                ]
            ];

            foreach ($articlesData as $articleData) {
                $category = $categoryMap[$articleData['categoryKey']] ?? null;
                if ($category === null) {
                    throw new \RuntimeException(sprintf(
                        'Nie znaleziono kategorii o kluczu %s dla artykułu %s.',
                        $articleData['categoryKey'],
                        $articleData['code']
                    ));
                }
                $article = new Article();
                $article->setCode($articleData['code']);
                $article->setEan13($articleData['ean13']);
                $article->setPrice($articleData['price']);
                $article->setIdCategory($category->getId());
                $article->setName($articleData['name']);
                $article->setDescription($articleData['description']);
                $entityManager->persist($article);
                ++$summary['articles'];
            }
            $entityManager->flush();

            $connection->commit();

            return new JsonResponse([
                'message' => 'Dodano przykładowe dane.',
                'summary' => $summary
            ], 201);
        } catch (\Throwable $exception) {
            $connection->rollBack();

            return new JsonResponse([
                'message' => 'Nie udało się zainicjalizować danych.',
                'error' => $exception->getMessage()
            ], 500);
        }
    }
}

