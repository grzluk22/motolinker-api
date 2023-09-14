<?php

namespace App\Controller;

use App\Entity\Reference;
use App\Repository\ReferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        return $this->json($references);

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
     **/
    #[Route('/reference', name: 'app_reference_post', methods: ["POST"])]
    public function post(ReferenceRepository $referenceRepository, Request $request)
    {
        /* Wstawia nowy numer referencyjny dla danego artykułu */
        $requestArray = $request->toArray();
        $reference = new Reference();
        $reference->setIdArticle($requestArray['id_article']);
        $reference->setBrand($requestArray['brand']);
        $reference->setNumber($requestArray['number']);
        $reference->setType($requestArray['type']);
        $referenceRepository->save($reference, true);;
        return $this->json($reference);
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
     **/
    #[Route('/reference', name: 'app_reference_delete', methods: ["DELETE"])]
    public function delete(ReferenceRepository $referenceRepository, Request $request)
    {
        /* Usuwa numer referencyjny */
        $requestArray = $request->toArray();
        $reference = $referenceRepository->findOneBy(['id' => $requestArray['id']]);
        $referenceRepository->remove($reference, true);
    }
}
