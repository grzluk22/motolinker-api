<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\CategoryLanguage;
use App\Entity\Car;
use App\Entity\Criterion;
use App\Entity\CriterionLanguage;
use App\Entity\Language;
use App\Entity\Reference;
use App\Entity\ReferenceType;
use App\Entity\UserGroup;
use App\Repository\ArticleRepository;
use App\Repository\CategoryLanguageRepository;
use App\Repository\CategoryRepository;
use App\Repository\CarRepository;
use App\Repository\CriterionLanguageRepository;
use App\Repository\CriterionRepository;
use App\Repository\LanguageRepository;
use App\Repository\ReferenceRepository;
use App\Repository\ReferenceTypeRepository;
use App\Repository\UserGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use App\HttpRequestModel\SeedSampleDataRequest;
use App\HttpResponseModel\SeedSampleDataResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SeedController extends AbstractController
{
    /**
     * Inicjuje bazę danych przykładowymi rekordami.
     */
    #[OA\Post(
        path: "/seed/sample-data",
        summary: "Uzupełnia bazę danymi demonstracyjnymi",
        tags: ["Seed"]
    )]
    #[OA\RequestBody(
        required: false,
        content: new Model(type: SeedSampleDataRequest::class)
    )]
    #[OA\Response(
        response: 201,
        description: "Dane zostały dodane",
        content: new Model(type: SeedSampleDataResponse::class)
    )]
    #[OA\Response(
        response: 500,
        description: "Wystąpił błąd podczas inicjalizacji danych"
    )]
    #[Route('/seed/sample-data', name: 'app_seed_sample_data', methods: ['POST'])]
    public function seedSampleData(
        Request $request,
        EntityManagerInterface $entityManager,
        LanguageRepository $languageRepository,
        CategoryRepository $categoryRepository,
        CategoryLanguageRepository $categoryLanguageRepository,
        CriterionRepository $criterionRepository,
        CriterionLanguageRepository $criterionLanguageRepository,
        CarRepository $carRepository,
        ArticleRepository $articleRepository,
        ReferenceTypeRepository $referenceTypeRepository,
        ReferenceRepository $referenceRepository
    ): JsonResponse {
        // Body of seedSampleData (restored from backup)
        $requestData = json_decode($request->getContent(), true) ?? [];
        $numCars = isset($requestData['numCars']) && is_numeric($requestData['numCars']) && $requestData['numCars'] > 0
            ? (int)$requestData['numCars']
            : 20;
        $numArticles = isset($requestData['numArticles']) && is_numeric($requestData['numArticles']) && $requestData['numArticles'] > 0
            ? (int)$requestData['numArticles']
            : 30;

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
                'articles' => 0,
                'reference_types' => 0,
                'references' => 0
            ];

            $languageMap = $this->seedLanguages($entityManager, $languageRepository, $summary);
            $categoryMap = $this->seedCategories($entityManager, $categoryRepository, $categoryLanguageRepository, $languageMap, $summary);
            $criterionMap = $this->seedCriteria($entityManager, $criterionRepository, $criterionLanguageRepository, $languageMap, $summary);
            $referenceTypeMap = $this->seedReferenceTypes($referenceTypeRepository, $summary);

            // Generowanie losowych danych dla samochodów
            $generatedCarsData = [];
            
            // Tabele wartości dla kolumn w tabeli cars
            $carColumnValues = [
                'manufacturer' => ['Audi', 'BMW', 'Mercedes-Benz', 'Volkswagen', 'Opel', 'Ford', 'Peugeot', 'Renault', 'Toyota', 'Volvo'],
                'model' => ['A4', 'A6', '320', '520', 'C-Class', 'E-Class', 'Golf', 'Passat', 'Astra', 'Insignia', 'Focus', 'Mondeo', '308', '508', 'Clio', 'Megane', 'Corolla', 'Camry', 'V40', 'V60'],
                'type' => ['B8', 'B9', 'F30', 'G20', 'W205', 'W213', 'VII', 'VIII', 'J', 'K', 'MK3', 'MK4', 'T9', 'T3', 'IV', 'V', 'E210', 'XV70', 'P1', 'SPA'],
                'model_from' => ['2005', '2008', '2010', '2012', '2014', '2016', '2018', '2020', '2022'],
                'model_to' => ['2010', '2012', '2015', '2017', '2019', '2021', '2023', '2025', null],
                'body_type' => ['Sedan', 'Hatchback', 'Estate', 'Coupe', 'SUV', 'Convertible', 'Wagon'],
                'drive_type' => ['FWD', 'RWD', 'AWD', '4WD'],
                'displacement_liters' => ['1.0', '1.2', '1.4', '1.6', '1.8', '2.0', '2.5', '3.0', '3.5', '4.0'],
                'displacement_cmm' => ['999', '1198', '1397', '1598', '1798', '1984', '2498', '2998', '3498', '3998'],
                'fuel_type' => ['Petrol', 'Diesel', 'Hybrid', 'Electric', 'LPG', 'CNG'],
                'kw' => ['50', '63', '77', '81', '96', '110', '132', '147', '165', '180', '220', '250', '300'],
                'hp' => ['68', '85', '105', '110', '130', '150', '180', '200', '225', '245', '300', '340', '408'],
                'cylinders' => [3, 4, 5, 6, 8],
                'valves' => ['8', '12', '16', '20', '24', '32'],
                'engine_type' => ['TFSI', 'TDI', 'TSI', 'CDI', 'HDI', 'dCi', 'Hybrid', 'Electric', 'Naturally Aspirated', 'Turbo'],
                'engine_codes' => ['CDNC', 'CLHA', 'B47', 'OM651', 'HDI', 'K9K', 'M20A', 'VEA', 'EA888', 'N20'],
                'kba' => ['0588/ASD', '0603/BVO', '0704/CWR', '0805/DXS', '0906/EYT', '1007/FZU', '1108/GAV', '1209/HBW', '1310/ICX', '1411/JDY'],
            ];

            for ($i = 0; $i < $numCars; $i++) {
                $manufacturer = $carColumnValues['manufacturer'][array_rand($carColumnValues['manufacturer'])];
                $model = $carColumnValues['model'][array_rand($carColumnValues['model'])];
                $type = $carColumnValues['type'][array_rand($carColumnValues['type'])];
                $modelFrom = $carColumnValues['model_from'][array_rand($carColumnValues['model_from'])];
                $modelToArray = array_filter($carColumnValues['model_to'], function($year) use ($modelFrom) {
                    return $year === null || (int)$year > (int)$modelFrom;
                });
                $modelTo = !empty($modelToArray) ? $modelToArray[array_rand($modelToArray)] : null;
                $bodyType = $carColumnValues['body_type'][array_rand($carColumnValues['body_type'])];
                $driveType = $carColumnValues['drive_type'][array_rand($carColumnValues['drive_type'])];
                $displacementLiters = $carColumnValues['displacement_liters'][array_rand($carColumnValues['displacement_liters'])];
                $displacementCmm = $carColumnValues['displacement_cmm'][array_rand($carColumnValues['displacement_cmm'])];
                $fuelType = $carColumnValues['fuel_type'][array_rand($carColumnValues['fuel_type'])];
                $kw = $carColumnValues['kw'][array_rand($carColumnValues['kw'])];
                $hp = $carColumnValues['hp'][array_rand($carColumnValues['hp'])];
                $cylinders = $carColumnValues['cylinders'][array_rand($carColumnValues['cylinders'])];
                $valves = $carColumnValues['valves'][array_rand($carColumnValues['valves'])];
                $engineType = $carColumnValues['engine_type'][array_rand($carColumnValues['engine_type'])];
                $engineCodes = $carColumnValues['engine_codes'][array_rand($carColumnValues['engine_codes'])];
                $kba = $carColumnValues['kba'][array_rand($carColumnValues['kba'])];
                $textValue = sprintf('%s %s %s %s %s', $manufacturer, $model, $type, $displacementLiters, $engineType);

                $car = new Car();
                $car->setManufacturer($manufacturer);
                $car->setModel($model);
                $car->setType($type);
                $car->setModelFrom($modelFrom);
                $car->setModelTo($modelTo ?? $modelFrom);
                $car->setBodyType($bodyType);
                $car->setDriveType($driveType);
                $car->setDisplacementLiters($displacementLiters);
                $car->setDisplacementCmm($displacementCmm);
                $car->setFuelType($fuelType);
                $car->setKw($kw);
                $car->setHp($hp);
                $car->setCylinders($cylinders);
                $car->setValves($valves);
                $car->setEngineType($engineType);
                $car->setEngineCodes($engineCodes);
                $car->setKba($kba);
                $car->setTextValue($textValue);
                $entityManager->persist($car);
                
                $generatedCarsData[] = [
                    'manufacturer' => $manufacturer,
                    'model' => $model,
                    'type' => $type
                ];
                ++$summary['cars'];
            }
            $entityManager->flush();

            // Pobierz wszystkie kategorie do losowego przypisania
            $allCategories = array_values($categoryMap);
            
            // Tabele wartości dla artykułów i referencji
            $articleColumnValues = [
                'code_prefix' => ['BRK', 'SUS', 'ENG', 'ELE', 'FIL', 'OIL', 'AIR', 'EXH', 'LIG', 'WIN'],
                'code_suffix' => ['001', '002', '101', '102', '201', '202', '301', '302', '401', '501', '601', '701', '801', '901'],
                'ean13_prefix' => ['590', '590', '590', '400', '400', '500', '500', '600', '600', '700'],
                'price_min' => 10.00,
                'price_max' => 2000.00,
                'name_prefix' => ['Zestaw', 'Komplet', 'Filtr', 'Pompa', 'Czujnik', 'Przewód', 'Kabel', 'Świeca', 'Wtyczka', 'Zawór'],
                'name_middle' => ['klocków', 'amortyzator', 'oleju', 'paliwa', 'powietrza', 'hamulcowy', 'spalinowy', 'zapłonowy', 'elektryczny', 'mechaniczny'],
                'name_suffix' => ['przód', 'tył', 'przedni', 'tylny', 'górny', 'dolny', 'lewy', 'prawy', 'kompletny', 'pojedynczy'],
                'description_template' => [
                    'Komplet {name} do {manufacturer} {model} {type}.',
                    '{name} kompatybilny z {manufacturer} {model}.',
                    'Wysokiej jakości {name} dla {manufacturer} {model} {type}.',
                    '{name} dedykowany do {manufacturer} {model}.',
                    'Oryginalny {name} do {manufacturer} {model} {type}.',
                    'Profesjonalny {name} dla {manufacturer} {model}.',
                ],
            ];

            $referenceColumnValues = [
                'brands' => ['BREMBO', 'BOSCH', 'TRW', 'ATE', 'FERODO', 'TEXTAR', 'PAGID', 'JURID', 'RAYBESTOS', 'WAGNER', 'MONROE', 'BILSTEIN', 'KYB', 'SACHS', 'DELPHI', 'VALEO', 'DENSO', 'NGK', 'MANN', 'MAHLE'],
                'number_prefix' => ['B', 'BR', 'TR', 'AT', 'FE', 'TX', 'PA', 'JU', 'RB', 'WG', 'MN', 'BL', 'KY', 'SA', 'DL', 'VL', 'DN', 'NG', 'MN', 'MH'],
                'number_suffix' => ['001', '002', '156', '203', '415', '521', '678', '789', '901', '234', '567', '890', '123', '456', '789', '012', '345', '678', '901', '234'],
                'number_middle' => ['O1', 'A2', 'B3', 'C4', 'D5', 'E6', 'F7', 'G8', 'H9', 'I0', 'J1', 'K2', 'L3', 'M4', 'N5', 'O6', 'P7', 'Q8', 'R9', 'S0'],
            ];

            // Generowanie losowych danych dla artykułów
            $usedCodes = [];
            for ($i = 0; $i < $numArticles; $i++) {
                $attempts = 0;
                $maxAttempts = 100;
                do {
                    $codePrefix = $articleColumnValues['code_prefix'][array_rand($articleColumnValues['code_prefix'])];
                    $codeSuffix = $articleColumnValues['code_suffix'][array_rand($articleColumnValues['code_suffix'])];
                    $sequential = str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT);
                    $code = $codePrefix . '-' . $codeSuffix . '-' . $sequential;
                    ++$attempts;
                } while ((in_array($code, $usedCodes) || $articleRepository->findOneBy(['code' => $code]) !== null) && $attempts < $maxAttempts);
                
                if ($attempts >= $maxAttempts) {
                    $code = $codePrefix . '-' . time() . '-' . rand(1000, 9999);
                }
                $usedCodes[] = $code;

                $eanPrefix = $articleColumnValues['ean13_prefix'][array_rand($articleColumnValues['ean13_prefix'])];
                $eanSuffix = str_pad((string)rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                $eanCheck = str_pad((string)rand(0, 9), 1, '0', STR_PAD_LEFT);
                $ean13 = $eanPrefix . $eanSuffix . $eanCheck;

                $price = round((rand((int)($articleColumnValues['price_min'] * 100), (int)($articleColumnValues['price_max'] * 100)) / 100), 2);
                $category = $allCategories[array_rand($allCategories)];

                $namePrefix = $articleColumnValues['name_prefix'][array_rand($articleColumnValues['name_prefix'])];
                $nameMiddle = $articleColumnValues['name_middle'][array_rand($articleColumnValues['name_middle'])];
                $nameSuffix = $articleColumnValues['name_suffix'][array_rand($articleColumnValues['name_suffix'])];
                $name = sprintf('%s %s %s', $namePrefix, $nameMiddle, $nameSuffix);

                $descriptionTemplate = $articleColumnValues['description_template'][array_rand($articleColumnValues['description_template'])];
                
                if (!empty($generatedCarsData)) {
                    $randomCar = $generatedCarsData[array_rand($generatedCarsData)];
                } else {
                    $allCars = $carRepository->findAll();
                    if (!empty($allCars)) {
                        $randomCarEntity = $allCars[array_rand($allCars)];
                        $randomCar = [
                            'manufacturer' => $randomCarEntity->getManufacturer() ?? 'Samochód',
                            'model' => $randomCarEntity->getModel() ?? '',
                            'type' => $randomCarEntity->getType() ?? ''
                        ];
                    } else {
                        $randomCar = ['manufacturer' => 'Samochód', 'model' => '', 'type' => ''];
                    }
                }
                
                $description = str_replace(
                    ['{name}', '{manufacturer}', '{model}', '{type}'],
                    [$name, $randomCar['manufacturer'], $randomCar['model'], $randomCar['type']],
                    $descriptionTemplate
                );

                $article = new Article();
                $article->setCode($code);
                $article->setEan13($ean13);
                $article->setPrice($price);
                $article->setIdCategory($category->getId());
                $article->setName($name);
                $article->setDescription($description);
                $entityManager->persist($article);
                $entityManager->flush();
                ++$summary['articles'];

                // Dodaj losowe referencje oryginalne (1-2 referencje)
                $numOriginalReferences = rand(1, 2);
                for ($j = 0; $j < $numOriginalReferences; $j++) {
                    $brand = $referenceColumnValues['brands'][array_rand($referenceColumnValues['brands'])];
                    $number = $referenceColumnValues['number_prefix'][array_rand($referenceColumnValues['number_prefix'])] . 
                              $referenceColumnValues['number_middle'][array_rand($referenceColumnValues['number_middle'])] . 
                              $referenceColumnValues['number_suffix'][array_rand($referenceColumnValues['number_suffix'])];

                    $reference = new Reference();
                    $reference->setIdArticle($article->getId());
                    $reference->setType($referenceTypeMap['Oryginalny']->getId());
                    $reference->setBrand($brand);
                    $reference->setNumber($number);
                    $referenceRepository->save($reference, true);
                    ++$summary['references'];
                }

                // Dodaj losowe referencje porównawcze (1-3 referencje)
                $numComparisonReferences = rand(1, 3);
                for ($j = 0; $j < $numComparisonReferences; $j++) {
                    $brand = $referenceColumnValues['brands'][array_rand($referenceColumnValues['brands'])];
                    $number = $referenceColumnValues['number_prefix'][array_rand($referenceColumnValues['number_prefix'])] . 
                              $referenceColumnValues['number_middle'][array_rand($referenceColumnValues['number_middle'])] . 
                              $referenceColumnValues['number_suffix'][array_rand($referenceColumnValues['number_suffix'])];

                    $reference = new Reference();
                    $reference->setIdArticle($article->getId());
                    $reference->setType($referenceTypeMap['Porównawczy']->getId());
                    $reference->setBrand($brand);
                    $reference->setNumber($number);
                    $referenceRepository->save($reference, true);
                    ++$summary['references'];
                }
            }
            
            $connection->commit();
            $categoryRepository->recalculateProductsCount();
            

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

    /**
     * Inicjuje bazę danych przykładowymi definicjami (języki, grupy użytkowników, kategorie, kryteria).
     */
    #[OA\Post(
        path: "/seed/sample-definitions",
        summary: "Uzupełnia bazę podstawowymi definicjami",
        tags: ["Seed"]
    )]
    #[OA\Response(
        response: 201,
        description: "Definicje zostały dodane"
    )]
    #[OA\Response(
        response: 500,
        description: "Wystąpił błąd podczas inicjalizacji definicji"
    )]
    #[Route('/seed/sample-definitions', name: 'app_seed_sample_definitions', methods: ['POST'])]
    public function seedSampleDefinitions(
        EntityManagerInterface $entityManager,
        LanguageRepository $languageRepository,
        CategoryRepository $categoryRepository,
        CategoryLanguageRepository $categoryLanguageRepository,
        CriterionRepository $criterionRepository,
        CriterionLanguageRepository $criterionLanguageRepository,
        UserGroupRepository $userGroupRepository
    ): JsonResponse {
        $connection = $entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $summary = [
                'languages' => 0,
                'user_groups' => 0,
                'categories' => 0,
                'category_translations' => 0,
                'criteria' => 0,
                'criterion_translations' => 0
            ];

            $languageMap = $this->seedLanguages($entityManager, $languageRepository, $summary);
            $this->seedUserGroups($entityManager, $userGroupRepository, $summary);
            $this->seedCategories($entityManager, $categoryRepository, $categoryLanguageRepository, $languageMap, $summary);
            $this->seedCriteria($entityManager, $criterionRepository, $criterionLanguageRepository, $languageMap, $summary);

            $connection->commit();

            return new JsonResponse([
                'message' => 'Dodano przykładowe definicje.',
                'summary' => $summary
            ], 201);
        } catch (\Throwable $exception) {
            $connection->rollBack();

            return new JsonResponse([
                'message' => 'Nie udało się zainicjalizować definicji.',
                'error' => $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine()
            ], 500);
        }
    }

    private function seedLanguages(EntityManagerInterface $entityManager, LanguageRepository $languageRepository, &$summary): array
    {
        $languageMap = [];
        $languagesData = [
            ['name' => 'Polski', 'isoCode' => 'pl'],
            ['name' => 'English', 'isoCode' => 'en'],
            ['name' => 'Deutsch', 'isoCode' => 'de']
        ];
        foreach ($languagesData as $languageData) {
            $existingLanguage = $languageRepository->findOneBy(['isoCode' => $languageData['isoCode']]);
            if ($existingLanguage === null) {
                $language = new Language();
                $language->setName($languageData['name']);
                $language->setIsoCode($languageData['isoCode']);
                $entityManager->persist($language);
                $languageMap[$languageData['isoCode']] = $language;
                if (isset($summary['languages'])) ++$summary['languages'];
            } else {
                $languageMap[$languageData['isoCode']] = $existingLanguage;
            }
        }
        $entityManager->flush();
        return $languageMap;
    }

    private function seedUserGroups(EntityManagerInterface $entityManager, UserGroupRepository $userGroupRepository, &$summary): void
    {
        $groupsData = [
            [
                'name' => 'Administrator',
                'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
                'isDefault' => false
            ],
            [
                'name' => 'Moderator',
                'roles' => ['ROLE_MODERATOR', 'ROLE_USER'],
                'isDefault' => false
            ],
            [
                'name' => 'Użytkownik',
                'roles' => ['ROLE_USER'],
                'isDefault' => true
            ]
        ];

        foreach ($groupsData as $groupData) {
            $existingGroup = $userGroupRepository->findOneBy(['name' => $groupData['name']]);
            if ($existingGroup === null) {
                $group = new UserGroup();
                $group->setName($groupData['name']);
                $group->setRoles($groupData['roles']);
                $group->setIsDefault($groupData['isDefault']);
                $entityManager->persist($group);
                if (isset($summary['user_groups'])) ++$summary['user_groups'];
            }
        }
        $entityManager->flush();
    }

    private function seedCategories(
        EntityManagerInterface $entityManager,
        CategoryRepository $categoryRepository,
        CategoryLanguageRepository $categoryLanguageRepository,
        array $languageMap,
        &$summary
    ): array {
        $categoryMap = [];
        $categoriesData = [
            'brakes' => [
                'id_parent' => 0,
                'translations' => [
                    ['isoCode' => 'pl', 'name' => 'Układ hamulcowy', 'description' => 'Elementy eksploatacyjne układu hamulcowego.'],
                    ['isoCode' => 'en', 'name' => 'Braking system', 'description' => 'Consumables for the braking system.']
                ]
            ],
            'suspension' => [
                'id_parent' => 0,
                'translations' => [
                    ['isoCode' => 'pl', 'name' => 'Zawieszenie', 'description' => 'Części układu zawieszenia i amortyzacji.'],
                    ['isoCode' => 'en', 'name' => 'Suspension', 'description' => 'Parts for suspension and damping systems.']
                ]
            ],
            'engine' => [
                'id_parent' => 0,
                'translations' => [
                    ['isoCode' => 'pl', 'name' => 'Silnik', 'description' => 'Podzespoły oraz akcesoria silnikowe.'],
                    ['isoCode' => 'en', 'name' => 'Engine', 'description' => 'Components and accessories for engines.']
                ]
            ]
        ];

        foreach ($categoriesData as $key => $categoryData) {
            $plTranslation = $categoryData['translations'][0];
            $existingCategoryLanguage = $categoryLanguageRepository->findOneBy(['name' => $plTranslation['name']]);
            
            if ($existingCategoryLanguage === null) {
                $category = new Category();
                $category->setIdParent($categoryData['id_parent']);
                $category->setProductsCount(0);
                $entityManager->persist($category);
                $entityManager->flush();
                $categoryMap[$key] = $category;
                if (isset($summary['categories'])) ++$summary['categories'];

                foreach ($categoryData['translations'] as $translationData) {
                    $language = $languageMap[$translationData['isoCode']] ?? null;
                    if ($language) {
                        $categoryLanguage = new CategoryLanguage();
                        $categoryLanguage->setIdCategory($category->getId());
                        $categoryLanguage->setIdLanguage($language->getId());
                        $categoryLanguage->setName($translationData['name']);
                        $categoryLanguage->setDescription($translationData['description']);
                        $entityManager->persist($categoryLanguage);
                        if (isset($summary['category_translations'])) ++$summary['category_translations'];
                    }
                }
                $entityManager->flush();
            } else {
                $existingCategory = $categoryRepository->find($existingCategoryLanguage->getIdCategory());
                if ($existingCategory) {
                    $categoryMap[$key] = $existingCategory;
                }
            }
        }
        return $categoryMap;
    }

    private function seedCriteria(
        EntityManagerInterface $entityManager,
        CriterionRepository $criterionRepository,
        CriterionLanguageRepository $criterionLanguageRepository,
        array $languageMap,
        &$summary
    ): array {
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
            $plTranslation = $criterionData['translations'][0];
            $existingCriterionLanguage = $criterionLanguageRepository->findOneBy(['name' => $plTranslation['name']]);
            
            if ($existingCriterionLanguage === null) {
                $criterion = new Criterion();
                $entityManager->persist($criterion);
                $entityManager->flush();
                $criterionMap[$key] = $criterion;
                if (isset($summary['criteria'])) ++$summary['criteria'];

                foreach ($criterionData['translations'] as $translationData) {
                    $language = $languageMap[$translationData['isoCode']] ?? null;
                    if ($language) {
                        $criterionLanguage = new CriterionLanguage();
                        $criterionLanguage->setIdCriterion($criterion->getId());
                        $criterionLanguage->setIdLanguage($language->getId());
                        $criterionLanguage->setName($translationData['name']);
                        $entityManager->persist($criterionLanguage);
                        if (isset($summary['criterion_translations'])) ++$summary['criterion_translations'];
                    }
                }
                $entityManager->flush();
            } else {
                $existingCriterion = $criterionRepository->find($existingCriterionLanguage->getIdCriterion());
                if ($existingCriterion) {
                    $criterionMap[$key] = $existingCriterion;
                }
            }
        }
        return $criterionMap;
    }

    private function seedReferenceTypes(ReferenceTypeRepository $referenceTypeRepository, &$summary): array
    {
        $referenceTypeMap = [];
        $referenceTypesData = [
            ['name' => 'Oryginalny'],
            ['name' => 'Porównawczy']
        ];
        
        foreach ($referenceTypesData as $typeData) {
            $existingType = $referenceTypeRepository->findOneBy(['name' => $typeData['name']]);
            if ($existingType === null) {
                $referenceType = new ReferenceType();
                $referenceType->setName($typeData['name']);
                $referenceTypeRepository->save($referenceType, true);
                $referenceTypeMap[$typeData['name']] = $referenceType;
                if (isset($summary['reference_types'])) ++$summary['reference_types'];
            } else {
                $referenceTypeMap[$typeData['name']] = $existingType;
            }
        }
        return $referenceTypeMap;
    }
}
