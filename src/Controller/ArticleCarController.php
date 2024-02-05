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
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Entity\Language;

class ArticleCarController extends AbstractController
{
    /**
     * Wyświetla liste samochodów podpiętych do danego artykułu
     *
     * @OA\Tag(name="ArticleCar")
     * @OA\Response(
     *     response=200,
     *     description="Lista samochodów podpiętych do danego artykułu",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *
     *                     }
     *             )
     *         })
     * )
     * * @OA\Response(
     *     response=404,
     *     description="Brak samochodów dla danego artykułów"
     * )
     *
     */
    #[Route('/article/{article_id}/car', name: 'app_article_car_get', methods: ["GET"])]
    public function index(ArticleCarRepository $articleCarRepository, int $article_id): JsonResponse
    {
        $result = $articleCarRepository->findBy(['id_article' => $article_id]);
        if(!$result) return new JsonResponse(['message' => 'Brak zastosowań dla danego artykułu'], 404);
        return new JsonResponse($result);
    }

    /**
     * Podpina samochód o danym id do artykułu o podanym id
     *
     * @OA\Tag(name="ArticleCar")
     * @OA\Response(
     *     response=200,
     *     description="Pomyślnie dodano")
     * )
     * * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono samochodu lub artykułu o podanym id"
     * )
     *
     */
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
     *
     * @OA\Tag(name="ArticleCar")
     * @OA\Response(
     *     response=200,
     *     description="Pomyślnie usunięto")
     * )
     * * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono samochodu lub artykułu o podanym id"
     * )
     *
     */
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
}
