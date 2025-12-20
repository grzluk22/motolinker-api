<?php

namespace App\Controller;

use App\Entity\ReferenceType;
use App\Repository\ReferenceTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

class ReferenceTypeController extends AbstractController
{
    /**
     * Pobiera typy numerów referencyjnych
     */
    #[OA\Tag(name: "ReferenceType")]
    #[OA\Response(
        response: 200,
        description: "Lista typów numerów referencyjnych",
        content: new OA\JsonContent(
            example: ["id" => 1, "name" => "Oryginalne"]
        )
    )]
    #[Route('/reference_type', name: 'app_article_reference_type_get', methods: ["GET"])]
    public function index(ReferenceTypeRepository $referenceTypeRepository)
    {
        /* Pobiera typy numerów referencyjnych */
        $referenceTypes = $referenceTypeRepository->findAll();
        if(!$referenceTypes) return new JsonResponse(['message' => 'Nie znaleziono żadnych typów numerów referencyjnych'], 404);
        return new JsonResponse($referenceTypes);
    }
    /**
     * Wstawia typ numeru referencyjnego
     */
    #[OA\Tag(name: "ReferenceType")]
    #[OA\RequestBody(
        description: "Nazwa typu",
        required: true,
        content: new OA\JsonContent(
            example: ["name" => "Oryginalny"]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Dodany typ numeru referencyjnego",
        content: new OA\JsonContent(
            example: ["id" => 1, "name" => "Oryginalne"]
        )
    )]
    #[OA\Response(
        response: 400,
        description: "ReferenceType o podanej nazwie już istnieje"
    )]
    #[Route('/reference_type', name: 'app_reference_type_post', methods: ["POST"])]
    public function post(ReferenceTypeRepository $referenceTypeRepository, Request $request): JsonResponse
    {
        $requestArray = $request->toArray();
        $referenceType = $referenceTypeRepository->findOneBy(['name' => $requestArray['name']]);
        if($referenceType !== null) return new JsonResponse(['message' => 'ReferenceType o podanej nazwie już istnieje'], 400);
        /* Wstawia nowy typ numeru referencyjnego */
        $referenceType = new ReferenceType();
        $referenceType->setName($requestArray['name']);
        $referenceTypeRepository->save($referenceType, true);
        return new JsonResponse($referenceType);
    }
    /**
     * Usuwa typ numeru referencyjnego
     */
    #[OA\Tag(name: "ReferenceType")]
    #[OA\Response(
        response: 200,
        description: "Usunieto"
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono typu numeru referencyjnego o podanym id"
    )]
    #[Route('/reference_type/{id_reference_type}', name: 'app_reference_type_delete', methods: ["DELETE"])]
    public function delete(ReferenceTypeRepository $referenceTypeRepository, int $id_reference_type)
    {
        /* Usuwa typ nueru referencyjnego */
        $referenceType = $referenceTypeRepository->findOneBy(['id' => $id_reference_type]);
        if(!$referenceType) return new JsonResponse(['message' => 'Nie znaleziono typu numeru referencyjnego'], 404);
        $referenceTypeRepository->remove($referenceType, true);
        return new JsonResponse(['message' => 'Usunięto']);
    }

    /**
     * Aktualizuje typ numeru referencyjnego
     */
    #[OA\Tag(name: "ReferenceType")]
    #[OA\RequestBody(
        description: "Parametry reference",
        required: true,
        content: new OA\JsonContent(
            example: ["id" => 1, "name" => "Oryginalny"]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Zmodyfikowany typ numeru referencyjnego",
        content: new OA\JsonContent(
            example: ["id" => 1, "name" => "Oryginalny"]
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono typu numeru referencyjnego o podanym id"
    )]
    #[Route('/reference_type', name: 'app_reference_type_put', methods: ["PUT"])]
    public function put(ReferenceTypeRepository $referenceTypeRepository, Request $request)
    {
        $requestArray = $request->toArray();
        $referenceType = $referenceTypeRepository->findOneBy(['id' => $requestArray['id']]);
        if(!$referenceType) return new JsonResponse(['message' => 'Nie znaleziono typu numeru referencyjnego o podanym id'], 404);
        $referenceType->setName($requestArray['name']);
        $referenceTypeRepository->save($referenceType, true);;
        return new JsonResponse($referenceType);
    }
}
