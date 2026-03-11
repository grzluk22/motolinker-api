<?php

namespace App\Controller;

use App\Entity\ArticleCar;
use App\Repository\ArticleCarRepository;
use App\Repository\ArticleRepository;
use App\Repository\CarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use function Symfony\Config\toArray;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use App\Entity\Language;
use App\HttpResponseModel\MessageResponse;
use App\HttpRequestModel\ArticleCarBulkEditRequest;

class ArticleCarController extends AbstractController
{
    /**
     * Wyświetla liste samochodów podpiętych do danego artykułu
     */
    #[OA\Tag(name: "ArticleCar")]
    #[OA\Response(
        response: 200,
        description: "Lista samochodów podpiętych do danego artykułu",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ArticleCar::class))
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Brak samochodów dla danego artykułów"
    )]
    #[Route('/article/{article_id}/car', name: 'app_article_car_get', methods: ["GET"])]
    public function index(ArticleCarRepository $articleCarRepository, int $article_id): JsonResponse
    {
        $result = $articleCarRepository->findBy(['id_article' => $article_id]);
        if(!$result) return new JsonResponse(['message' => 'Brak zastosowań dla danego artykułu'], 404);
        return new JsonResponse($result);
    }

    /**
     * Podpina samochód o danym id do artykułu o podanym id
     */
    #[OA\Tag(name: "ArticleCar")]
    #[OA\Response(
        response: 200,
        description: "Pomyślnie dodano"
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono samochodu lub artykułu o podanym id"
    )]
    #[Route('/article/{article_id}/car/{car_id}', name: 'app_article_car_post', methods: ["POST"])]
    public function post(ArticleRepository $articleRepository, CarRepository $carRepository, ArticleCarRepository $articleCarRepository, int $article_id, int $car_id): JsonResponse
    {
        /* Sprawdzanie czy samochód o podanym id nie został już podłączony do tego artykułu w celu uniknięcia tworzenia duplikatów */
        $articleCar = $articleCarRepository->findOneBy(['id_article' => $article_id, 'id_car' => $car_id]);
        if($articleCar) {
            return new JsonResponse(['message' => 'Samochód o podanym id już jest połączony z produktem'], 400);
        }

        /* Sprawdzanie czy istnieje artykuł o podanym id */
        $article = $articleRepository->findOneBy(['id' => $article_id]);
        if($article === null) {
            return new JsonResponse(["message" => "Nie znaleziono artykułu o podanym id"], 404);
        }

        /* Sprawdzanie czy istnieje samochód o podanym id */
        $car = $carRepository->findOneBy(['id' => $car_id]);
        if($car === null) {
            return new JsonResponse(['message' => 'Nie znaleziono samochodu o podanym id'], 404);
        }

        $articleCar = new ArticleCar();
        $articleCar->setIdArticle($article_id);
        $articleCar->setIdCar($car_id);
        $articleCarRepository->save($articleCar, true);
        $response = ["message" => "podłączono samochód do produktu"];
        return new JsonResponse($response);
    }

    /**
     * Odpina samochód o danym id od artykułu o podanym id
     */
    #[OA\Tag(name: "ArticleCar")]
    #[OA\Response(
        response: 200,
        description: "Pomyślnie usunięto",
        content: new Model(type: MessageResponse::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono samochodu lub artykułu o podanym id"
    )]
    #[Route('/article/{article_id}/car/{car_id}', name: 'app_article_car_delete', methods: ["DELETE"])]
    public function delete(ArticleCarRepository $articleCarRepository, int $article_id, int $car_id): JsonResponse
    {
        $articleCar = $articleCarRepository->findOneBy(['id_article' => $article_id, 'id_car' => $car_id]);
        if($articleCar !== null) {
            $articleCarRepository->remove($articleCar, true);
            return new JsonResponse(["message" => "Odłączono samochód do produktu"]);
        }else{
            return new JsonResponse(["message" => "nie znaleziono takiego samochodu podłączonego do artykułu"], 404);
        }
    }
    /**
     * Zbiorcza edycja powiązań artykułów z samochodami
     */
    #[OA\Tag(name: "ArticleCar")]
    #[OA\RequestBody(
        description: "Lista operacji do wykonania (dodawanie/usuwanie powiązań)",
        required: true,
        content: new Model(type: ArticleCarBulkEditRequest::class)
    )]
    #[OA\Response(
        response: 200,
        description: "Wynik operacji",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string"),
                new OA\Property(property: "processed_count", type: "integer"),
                new OA\Property(property: "errors", type: "array", items: new OA\Items(type: "string"))
            ]
        )
    )]
    #[Route('/article/bulk-cars-edit', name: 'app_article_bulk_cars_edit', methods: ["POST"])]
    public function bulkEdit(
        ManagerRegistry $doctrine,
        ArticleCarRepository $articleCarRepository,
        ArticleRepository $articleRepository,
        CarRepository $carRepository,
        Request $request
    ): JsonResponse {
        $entityManager = $doctrine->getManager();
        $data = $request->toArray();
        
        $operations = $data['operations'] ?? [];
        
        // Wsteczna kompatybilność / elastyczność
        if (empty($operations) && isset($data[0]) && is_array($data[0])) {
            $operations = $data;
        }

        if (empty($operations)) {
            return new JsonResponse(['message' => 'Brak operacji do wykonania', 'processed_count' => 0], 400);
        }

        $processedCount = 0;
        $errors = [];

        // Cache dla encji
        $articlesCache = [];
        $carsCache = [];

        foreach ($operations as $index => $op) {
            $articleId = $op['article_id'] ?? null;
            $carId = $op['car_id'] ?? null;
            $action = $op['action'] ?? null;

            if (!$articleId || !$carId || !$action) {
                $errors[] = "Operacja #$index: Brak wymaganych pól (article_id, car_id, action)";
                continue;
            }

            try {
                // Walidacja istnienia artykułu
                if (!isset($articlesCache[$articleId])) {
                    $article = $articleRepository->find($articleId);
                    if (!$article) {
                        $errors[] = "Operacja #$index: Nie znaleziono artykułu ID $articleId";
                        $articlesCache[$articleId] = false;
                        continue;
                    }
                    $articlesCache[$articleId] = $article;
                } elseif ($articlesCache[$articleId] === false) {
                    continue;
                }

                // Walidacja istnienia samochodu
                if (!isset($carsCache[$carId])) {
                     $car = $carRepository->find($carId);
                     if (!$car) {
                         $errors[] = "Operacja #$index: Nie znaleziono samochodu ID $carId";
                         $carsCache[$carId] = false;
                         continue;
                     }
                     $carsCache[$carId] = $car;
                } elseif ($carsCache[$carId] === false) {
                     continue;
                }

                $existingRelation = $articleCarRepository->findOneBy(['id_article' => $articleId, 'id_car' => $carId]);

                if ($action === 'add') {
                    if (!$existingRelation) {
                        $newRelation = new ArticleCar();
                        $newRelation->setIdArticle($articleId);
                        $newRelation->setIdCar($carId);
                        $entityManager->persist($newRelation);
                        $processedCount++;
                    }
                } elseif ($action === 'remove') {
                    if ($existingRelation) {
                        $entityManager->remove($existingRelation);
                        $processedCount++;
                    }
                } else {
                    $errors[] = "Operacja #$index: Nieznana akcja '$action'";
                }

            } catch (\Exception $e) {
                $errors[] = "Operacja #$index: Błąd wewnętrzny - " . $e->getMessage();
            }
        }

        if ($processedCount > 0) {
            $entityManager->flush();
        }

        return new JsonResponse([
            'message' => 'Zakończono masową edycję powiązań',
            'processed_count' => $processedCount,
            'errors' => $errors
        ]);
    }
}
