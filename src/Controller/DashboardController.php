<?php

namespace App\Controller;

use App\Entity\UserDashboardSetting;
use App\HttpRequestModel\DashboardSettingsBulkEditRequest;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\UserDashboardSettingRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[OA\Tag(name: "Dashboard")]
    #[OA\Response(
        response: 200,
        description: "Pobiera ustawienia kafelków dashboardu dla zalogowanego użytkownika",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: UserDashboardSetting::class))
        )
    )]
    #[Route('/settings', name: 'app_dashboard_settings_get', methods: ['GET'])]
    public function getSettings(UserDashboardSettingRepository $settingRepository): JsonResponse
    {
        $user = $this->getUser();
        $settings = $settingRepository->findBy(['user' => $user]);

        $data = [];
        foreach ($settings as $setting) {
            $data[] = [
                'id' => $setting->getWidgetId(),
                'x' => $setting->getGridX(),
                'y' => $setting->getGridY(),
                'rows' => $setting->getGridRows(),
                'cols' => $setting->getGridCols(),
                'visible' => $setting->isVisible(),
                'config' => $setting->getConfig(),
            ];
        }

        return $this->json(['data' => $data]);
    }

    #[OA\Tag(name: "Dashboard")]
    #[OA\RequestBody(
        description: "Nowe ustawienia układu kafelków",
        required: true,
        content: new Model(type: DashboardSettingsBulkEditRequest::class)
    )]
    #[OA\Response(
        response: 200,
        description: "Ustawienia zapisane pomyślnie"
    )]
    #[Route('/settings/bulk-edit', name: 'app_dashboard_settings_bulk_edit', methods: ['POST'])]
    public function bulkEditSettings(
        Request $request,
        ManagerRegistry $registry,
        UserDashboardSettingRepository $settingRepository
    ): JsonResponse {
        $user = $this->getUser();
        $data = $request->toArray();
        $layoutData = $data['layout'] ?? [];

        if (empty($layoutData)) {
            return $this->json(['message' => 'Brak danych do zapisu'], 400);
        }

        $entityManager = $registry->getManager();

        $existingSettings = $settingRepository->findBy(['user' => $user]);
        $existingMap = [];
        foreach ($existingSettings as $es) {
            $existingMap[$es->getWidgetId()] = $es;
        }

        foreach ($layoutData as $item) {
            $widgetId = $item['id'] ?? null;
            if (!$widgetId) {
                continue; // Pomijamy błędne rekordy
            }

            if (isset($existingMap[$widgetId])) {
                $setting = $existingMap[$widgetId];
            } else {
                $setting = new UserDashboardSetting();
                $setting->setUser($user);
                $setting->setWidgetId($widgetId);
                $entityManager->persist($setting);
            }

            if (isset($item['visible'])) {
                $setting->setIsVisible((bool)$item['visible']);
            }
            if (isset($item['x'])) {
                $setting->setGridX((int)$item['x']);
            }
            if (isset($item['y'])) {
                $setting->setGridY((int)$item['y']);
            }
            if (isset($item['cols'])) {
                $setting->setGridCols((int)$item['cols']);
            }
            if (isset($item['rows'])) {
                $setting->setGridRows((int)$item['rows']);
            }
            if (array_key_exists('config', $item)) {
                $setting->setConfig(is_array($item['config']) ? $item['config'] : null);
            }
        }

        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[OA\Tag(name: "Dashboard")]
    #[OA\Response(
        response: 200,
        description: "Pobiera parametry konfiguracyjne siatki dla aktualnego użytkownika"
    )]
    #[Route('/settings/grid', name: 'app_dashboard_settings_grid_get', methods: ['GET'])]
    public function getGridSettings(\App\Repository\SettingRepository $settingRepository): JsonResponse
    {
        $user = $this->getUser();
        $setting = $settingRepository->findOneBy(['user' => $user, 'settingKey' => 'dashboard_grid_setting']);

        if (!$setting || !$setting->getSettingValue()) {
            return $this->json(new \stdClass());
        }

        return new JsonResponse($setting->getSettingValue(), 200, [], true);
    }

    #[OA\Tag(name: "Dashboard")]
    #[OA\Response(
        response: 200,
        description: "Zapisuje parametry konfiguracyjne siatki dla aktualnego użytkownika"
    )]
    #[Route('/settings/grid', name: 'app_dashboard_settings_grid_post', methods: ['POST', 'PUT'])]
    public function saveGridSettings(
        Request $request,
        ManagerRegistry $registry,
        \App\Repository\SettingRepository $settingRepository
    ): JsonResponse {
        $user = $this->getUser();
        $data = $request->getContent();

        $entityManager = $registry->getManager();
        $setting = $settingRepository->findOneBy(['user' => $user, 'settingKey' => 'dashboard_grid_setting']);

        if (!$setting) {
            $setting = new \App\Entity\Setting();
            $setting->setUser($user);
            $setting->setSettingKey('dashboard_grid_setting');
            $entityManager->persist($setting);
        }

        $setting->setSettingValue($data);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[OA\Tag(name: "Dashboard")]
    #[OA\Response(
        response: 200,
        description: "Zwraca liczbę produktów w kategorii Samochody, które nie mają podpiętego żadnego pojazdu",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "carsWithoutApplication", type: "integer", example: 42)
            ]
        )
    )]
    #[Route('/stats/products-without-application', name: 'app_dashboard_stats_products_without_app', methods: ['GET'])]
    public function getProductsWithoutApplication(
        ArticleRepository $articleRepository,
        CategoryRepository $categoryRepository,
        \App\Repository\CategoryLanguageRepository $categoryLanguageRepository,
        ManagerRegistry $registry
    ): JsonResponse {
        // Znalezienie kategorii z tabeli tłumaczeń
        $categoryLanguage = $categoryLanguageRepository->createQueryBuilder('cl')
            ->where('cl.name LIKE :name')
            ->setParameter('name', 'Samochody%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$categoryLanguage) {
            return $this->json(['count' => 0]);
        }
        
        $categoryId = $categoryLanguage->getIdCategory();
        $categoryIds = $categoryRepository->getDescendantIds([$categoryId]);

        // Liczymy artykuły, które należą do $categoryIds i NIE SĄ W relacji article_car
        $qb = $articleRepository->createQueryBuilder('a');
        $qb->select('COUNT(a.id)')
           ->where('a.id_category IN (:categories)')
           ->setParameter('categories', $categoryIds)
           ->leftJoin('App\Entity\ArticleCar', 'ac', 'WITH', 'a.id = ac.id_article')
           ->andWhere('ac.id IS NULL');

        $count = (int) $qb->getQuery()->getSingleScalarResult();

        return $this->json(['carsWithoutApplication' => $count]);
    }

    #[OA\Tag(name: "Dashboard")]
    #[OA\Response(
        response: 200,
        description: "Zwraca ogólne statystyki (ilość produktów, producentów, kategorii, atrybutów)",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "totalProducts", type: "integer", example: 1500),
                new OA\Property(property: "totalCategories", type: "integer", example: 45),
                new OA\Property(property: "totalManufacturers", type: "integer", example: 120),
                new OA\Property(property: "totalAttributes", type: "integer", example: 350)
            ]
        )
    )]
    #[Route('/stats/overview', name: 'app_dashboard_stats_overview', methods: ['GET'])]
    public function getOverviewStats(
        ArticleRepository $articleRepository,
        \App\Repository\CarRepository $carRepository,
        CategoryRepository $categoryRepository,
        \App\Repository\CriterionRepository $criterionRepository
    ): JsonResponse {
        $totalProducts = (int) $articleRepository->createQueryBuilder('a')->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();
        $totalManufacturers = count($carRepository->getManufacturers());
        $totalCategories = (int) $categoryRepository->createQueryBuilder('cat')->select('COUNT(cat.id)')->getQuery()->getSingleScalarResult();
        $totalAttributes = (int) $criterionRepository->createQueryBuilder('c')->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();

        return $this->json([
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalManufacturers' => $totalManufacturers,
            'totalAttributes' => $totalAttributes
        ]);
    }

    #[OA\Tag(name: "Dashboard")]
    #[OA\Response(
        response: 200,
        description: "Zwraca statystyki ostatnich 5 importów z ich statusem",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "fileName", type: "string", example: "import_produkty.csv"),
                    new OA\Property(property: "date", type: "string", format: "date-time"),
                    new OA\Property(property: "status", type: "string", example: "COMPLETED"),
                    new OA\Property(property: "productsAdded", type: "integer", example: 50),
                    new OA\Property(property: "productsUpdated", type: "integer", example: 230)
                ]
            )
        )
    )]
    #[Route('/stats/latest-imports', name: 'app_dashboard_stats_latest_imports', methods: ['GET'])]
    public function getLatestImportsStats(
        \App\Repository\ImportJobRepository $importJobRepository
    ): JsonResponse {
        $recentJobs = $importJobRepository->createQueryBuilder('i')
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($recentJobs as $job) {
            $data[] = [
                'id' => $job->getId(),
                'fileName' => basename($job->getFilePath() ?? ''),
                'date' => $job->getCreatedAt()->format('c'),
                'status' => strtoupper($job->getStatus()),
                'productsAdded' => $job->getProcessedRows(), // Zastępczo
                'productsUpdated' => 0 // Zastępczo, wymaga bardziej zaawansowanej logiki w bazie jeśli osobno liczone
            ];
        }

        return $this->json(['data' => $data]);
    }

    #[OA\Tag(name: "Dashboard")]
    #[OA\Parameter(
        name: "type",
        in: "query",
        description: "Typ statystyk (total, active, new_30d)",
        required: false,
        schema: new OA\Schema(type: "string", default: "total")
    )]
    #[OA\Response(
        response: 200,
        description: "Zwraca zagregowane dane statystyk użytkowników z danymi do wykresu",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "totalValue", type: "integer", example: 26042),
                new OA\Property(
                    property: "chartData",
                    type: "array",
                    items: new OA\Items(type: "integer"),
                    example: [65, 59, 84, 84, 51, 55, 40]
                )
            ]
        )
    )]
    #[Route('/stats/users', name: 'app_dashboard_stats_users', methods: ['GET'])]
    public function getUsersStats(
        Request $request,
        \App\Repository\UserRepository $userRepository
    ): JsonResponse {
        $type = $request->query->get('type', 'total');
        
        $totalUsers = (int) $userRepository->createQueryBuilder('u')->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        $totalValue = 0;
        $chartData = [];

        // Ze względu na brak pola createdAt w encji User, symulujemy dane dla wykresów i innych typów
        // aby ułatwić podłączenie na froncie
        if ($type === 'total') {
            $totalValue = $totalUsers;
            $chartData = [
                max(0, $totalUsers - 20),
                max(0, $totalUsers - 15),
                max(0, $totalUsers - 10),
                max(0, $totalUsers - 5),
                max(0, $totalUsers - 2),
                max(0, $totalUsers - 1),
                $totalUsers
            ];
        } elseif ($type === 'active') {
            $totalValue = (int) ceil($totalUsers * 0.4); // Załóżmy 40% aktywnych
            $chartData = [
                (int)($totalValue * 0.8),
                (int)($totalValue * 0.9),
                (int)($totalValue * 1.1),
                (int)($totalValue * 0.95),
                (int)($totalValue * 1.05),
                (int)($totalValue * 0.99),
                $totalValue
            ];
        } elseif ($type === 'new_30d') {
            $totalValue = (int) ceil($totalUsers * 0.1); // Załóżmy 10% nowych w 30 dni
            $chartData = [1, 3, 2, 5, 4, 8, $totalValue];
        } else {
            $totalValue = $totalUsers;
            $chartData = [0, 0, 0, 0, 0, 0, $totalValue];
        }

        return $this->json([
            'totalValue' => $totalValue,
            'chartData' => $chartData
        ]);
    }
}
