<?php

namespace App\Controller;

use App\Entity\Reference;
use App\Repository\ArticleRepository;
use App\Repository\ReferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class ReferenceController extends AbstractController
{
    /**
     * Pobiera numery referencyjne
     *
     * Parametr type w RequestBody nie jest obowiązkowy, w przypadku jego braku metoda zwróci wszystkie numery referencjne
     *
     * @OA\Tag(name="Reference")
     * @OA\RequestBody(
     *     request="ReferenceGetBody",
     *     description="Parametry reference",
     *     required=true,
     *     @OA\JsonContent(
     *                     example={
     *                         "id_article": 26,
     *                         "type": 2,
     *                     }
     *    )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Lista numerów referencyjnych",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                      "id": 1,
     *                      "id_article": 26,
     *                      "type": 2,
     *                      "brand": "BREMBO",
     *                      "number": "B156O1"
     *                     }
     *             )
     *         })
     * )
     **/
    #[Route('/reference', name: 'app_reference_get', methods: ["GET"])]
    public function index(ReferenceRepository $referenceRepository, Request $request)
    {
        /* Pobiera numery referencyjne dla id artykułu przekazanego w body żądania, jeżeli przekazano też typ to pobiera tylko te (porównawcze oraz oryginalne) */
        $requestArray = $request->toArray();
        if(isset($requestArray['type'])) {
            $references = $referenceRepository->findBy(['id_article' => $requestArray['id_article'], 'type' => $requestArray['type']]);
        }else{
            $references = $referenceRepository->findBy(['id_article' => $requestArray['id_article']]);
        }
        if(!$references) return new JsonResponse(['message' => 'Nie znaleziono numerów porównawczych'], 404);
        return new JsonResponse($references);

    }
    /**
     * Wstawia numery referencyjne
     *
     * @OA\Tag(name="Reference")
     * @OA\RequestBody(
     *     request="ReferencePostBody",
     *     description="Parametry reference",
     *     required=true,
     *     @OA\JsonContent(
     *                     example={
     *                      "id_article": 26,
     *                      "type": 2,
     *                      "brand": "BREMBO",
     *                      "number": "B156O1"
     *                     }
     *    )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Lista numerów referencyjnych",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                      "id": 1,
     *                      "id_article": 26,
     *                      "type": 2,
     *                      "brand": "BREMBO",
     *                      "number": "B156O1"
     *                     }
     *             )
     *         })
     * )
     * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono artykułu o podanym id"
     * )
     **/
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
     * Usuwa numery referencyjne
     *
     * @OA\Tag(name="Reference")
     * @OA\RequestBody(
     *     request="ReferenceDeleteBody",
     *     description="Parametry reference",
     *     required=true,
     *     @OA\JsonContent(
     *                     example={
     *                         "id": 1
     *                     }
     *    )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Usunieto",
     * )
     * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono numeru referencyjnego o podanym id"
     * )
     **/
    #[Route('/reference', name: 'app_reference_delete', methods: ["DELETE"])]
    public function delete(ReferenceRepository $referenceRepository, Request $request)
    {
        /* Usuwa numer referencyjny */
        $requestArray = $request->toArray();
        $reference = $referenceRepository->findOneBy(['id' => $requestArray['id']]);
        if(!$reference) return new JsonResponse(['message' => 'Nie znaleziono numeru referencyjnego'], 404);
        $referenceRepository->remove($reference, true);
        return new JsonResponse(['message' => 'Usunięto']);
    }
}
