<?php

namespace App\Controller;

use App\Entity\Reference;
use App\Repository\ArticleRepository;
use App\Repository\ReferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use App\HttpRequestModel\ReferenceCreateRequest;
use App\HttpRequestModel\ReferenceUpdateRequest;
use App\HttpResponseModel\ReferenceResponse;
use App\HttpResponseModel\MessageResponse;

class ReferenceController extends AbstractController
{
    /**
     * Pobiera numery referencyjne
     *
     * Parametr type w RequestBody nie jest obowiązkowy, w przypadku jego braku metoda zwróci wszystkie numery referencjne
     */
    #[OA\Tag(name: "Reference")]
    #[OA\Response(
        response: 200,
        description: "Lista numerów referencyjnych",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Reference::class))
        )
    )]
    #[Route('/reference/{id_article}', name: 'app_article_reference_get', methods: ["GET"])]
    public function index(ReferenceRepository $referenceRepository, int $id_article)
    {
        /* Pobiera numery referencyjne dla id artykułu */
        $references = $referenceRepository->findBy(['id_article' => $id_article]);
        if(!$references) return new JsonResponse(['message' => 'Nie znaleziono numerów porównawczych'], 404);
        return new JsonResponse($references);

    }
    /**
     * Wstawia numery referencyjne
     */
    #[OA\Tag(name: "Reference")]
    #[OA\RequestBody(
        description: "Parametry reference",
        required: true,
        content: new Model(type: ReferenceCreateRequest::class)
    )]
    #[OA\Response(
        response: 200,
        description: "Dodany numer referencyjny",
        content: new Model(type: Reference::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono artykułu o podanym id"
    )]
    #[Route('/reference', name: 'app_reference_post', methods: ["POST"])]
    public function post(ReferenceRepository $referenceRepository, ArticleRepository $articleRepository, Request $request)
    {
        $requestArray = $request->toArray();
        $article = $articleRepository->findOneBy(['id' => $requestArray['id_article']]);
        if(!$article) return new JsonResponse(['message' => 'Nie znaleziono artykułu o podanym id'], 404);
        /* Wstawia nowy numer referencyjny dla danego artykułu */
        $reference = new Reference();
        $reference->setIdArticle($requestArray['id_article']);
        $reference->setBrand($requestArray['brand']);
        $reference->setNumber($requestArray['number']);
        $reference->setType($requestArray['type']);
        $referenceRepository->save($reference, true);;
        return new JsonResponse($reference);
    }

    /**
     * Aktualizuje numery referencyjne
     */
    #[OA\Tag(name: "Reference")]
    #[OA\RequestBody(
        description: "Parametry reference",
        required: true,
        content: new Model(type: ReferenceUpdateRequest::class)
    )]
    #[OA\Response(
        response: 200,
        description: "Zaktualizowany numer referencyjny",
        content: new Model(type: Reference::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono artykułu lub numeru referencyjnego o podanym id"
    )]
    #[Route('/reference', name: 'app_reference_put', methods: ["PUT"])]
    public function put(ReferenceRepository $referenceRepository, ArticleRepository $articleRepository, Request $request)
    {
        $requestArray = $request->toArray();
        $article = $articleRepository->findOneBy(['id' => $requestArray['id_article']]);
        if(!$article) return new JsonResponse(['message' => 'Nie znaleziono artykułu o podanym id'], 404);
        /* Aktualizuje nowy numer referencyjny dla danego artykułu */
        $reference = $referenceRepository->findOneBy(['id' => $requestArray['id']]);
        if(!$reference) return new JsonResponse(['message' => 'Nie znaleziono numeru referencyjnego o podanym id'], 404);
        $reference->setIdArticle($requestArray['id_article']);
        $reference->setBrand($requestArray['brand']);
        $reference->setNumber($requestArray['number']);
        $reference->setType($requestArray['type']);
        $referenceRepository->save($reference, true);;
        return new JsonResponse($reference);
    }

    /**
     * Usuwa numery referencyjne
     */
    #[OA\Tag(name: "Reference")]
    #[OA\Response(
        response: 200,
        description: "Usunieto",
        content: new Model(type: MessageResponse::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono numeru referencyjnego o podanym id"
    )]
    #[Route('/reference/{id_reference}', name: 'app_reference_delete', methods: ["DELETE"])]
    public function delete(ReferenceRepository $referenceRepository, int $id_reference)
    {
        /* Usuwa numer referencyjny */
        $reference = $referenceRepository->findOneBy(['id' => $id_reference]);
        if(!$reference) return new JsonResponse(['message' => 'Nie znaleziono numeru referencyjnego'], 404);
        $referenceRepository->remove($reference, true);
        return new JsonResponse(['message' => 'Usunięto']);
    }
}
