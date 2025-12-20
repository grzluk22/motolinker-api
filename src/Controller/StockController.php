<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Repository\StockRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use function Symfony\Component\Cache\Traits\object;

class StockController extends AbstractController
{
    /**
     * Lista wszystkich magazynów
     */
    #[OA\Tag(name: "Stock")]
    #[OA\Response(
        response: 200,
        description: "Lista kryteriów",
        content: new OA\JsonContent(
            example: ["id" => 1, "name" => "Antwerpia"]
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Brak Magazynów"
    )]
    #[Route('/stock', name: 'app_stock_get', methods: ['GET'])]
        public function index(StockRepository $stockRepository): JsonResponse
        {
            $repositories = $stockRepository->findAll();
            return new JsonResponse($repositories);
        }

    /**
     * Tworzy nowy magazyn
     */
    #[OA\Tag(name: "Stock")]
    #[OA\RequestBody(
        description: "Magazyn",
        required: true,
        content: new OA\JsonContent(
            example: ["name" => "Antwerpia"]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Dodany magazyn",
        content: new OA\JsonContent(
            example: ["id" => 1, "name" => "Antwerpia"]
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Brak Magazynu"
    )]
    #[Route('/stock', name: 'app_stock_add', methods: ['POST'])]
    public function add(StockRepository $stockRepository, Request $request) {
        /* Żadanie dodania nowego kryterium przyjmuje tylko tłumaczenia nazw dla danego kryterium */
        $requestArray = $request->toArray();
        $stock = new Stock();
        $stock->setName($requestArray['name']);
        $stockRepository->save($stock, true);
        return new JsonResponse($stock);
    }

    /**
     * Edytuje magazyn
     */
    #[OA\Tag(name: "Stock")]
    #[OA\RequestBody(
        description: "Magazyn",
        required: true,
        content: new OA\JsonContent(
            example: ["id" => 1, "name" => "Antwerpia"]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Zaktualizowany magazyn",
        content: new OA\JsonContent(
            example: ["id" => 1, "name" => "Antwerpia"]
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono magzynu o podanym id"
    )]
    #[Route('/stock', name: 'app_stock_edit', methods: ['PUT'])]
    public function edit(StockRepository $stockRepository, Request $request) {
        /* Żadanie dodania nowego kryterium przyjmuje tylko tłumaczenia nazw dla danego kryterium */
        $requestArray = $request->toArray();
        $stock = $stockRepository->findOneBy(['id' => $requestArray['id']]);
        if(!$stock) return new JsonResponse(['error' => 'Nie znaleziono magazynu o podanym id'], 404);
        $stock->setName($requestArray['name']);
        $stockRepository->save($stock, true);
        return new JsonResponse($stock);
    }

    /**
     * Usuwa magazyn
     */
    #[OA\Tag(name: "Stock")]
    #[OA\Response(
        response: 200,
        description: "Usunięto"
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono magazynu o podanym id"
    )]
    #[Route('/stock/{id}', name: 'app_stock_delete', methods: ["DELETE"])]
    public function delete(ManagerRegistry $doctrine, StockRepository $stockRepository, string $id): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $stock = $stockRepository->findOneBy(["id" => $id]);
        if($stock === null) {
            return new JsonResponse(["message" => "Nie znaleziono magazynu o podanym id"], 404);
        }
        /* Usuwanie kategorii */
        $entityManager->remove($stock);
        $entityManager->flush();

        return new JsonResponse(['message' => "Usunięto"]);
    }
}
