<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use function Symfony\Config\toArray;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

class ArticleController extends AbstractController
{
    /**
     * Wyświetla liste artykułów
     *
     * Dodatkowy opis metody do NelmioApiDocBundle
     */
    #[Route('/article', name: 'app_article_get', methods: ["GET"])]
    public function index(ArticleRepository $articleRepository): JsonResponse
    {
        $result = $articleRepository->findAll();
        $data = ["code" => $result[0]->getCode()];
        return $this->json($result);
    }

    #[Route('/article/{code}', name: 'app_article_get_by_code', methods: ["GET"])]
    public function getByCode(ArticleRepository $articleRepository, string $code): JsonResponse
    {
        $result = $articleRepository->findOneByCode($code);
        return $this->json($result);
    }



    /**
     * Tworzy nowy artykuł
     *
     * Przyjmuje pola arykułu
     */
    #[Route('/article', name: 'app_article_create', methods: ["POST"])]
    /**
     * @OA\Response(
     *     response=200,
     *     description="Stworzono artykuł",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="code",
     *                         type="string",
     *                         description="Kod artykułu"
     *                     ),
     *                     @OA\Property(
     *                         property="ean13",
     *                         type="string",
     *                         description="Kod kreskowy artykułu"
     *                     ),
     *                     @OA\Property(
     *                         property="price",
     *                         type="float",
     *                         description="Cena artykułu",
     *                     ),
     *                     @OA\Property(
     *                         property="idCategory",
     *                         type="integer",
     *                         description="Domyślne id kategorii",
     *                     ),
     *                     example={
     *                         "code": "36790-SET-MS",
     *                         "ean13": "1234567890123",
     *                         "price": "367.99",
     *                         "idCategory": "0"
     *                     }
     *                 )
     *             )
     *         })
     * )
    **/
    public function create(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $requestArray = $request->toArray();
        /* Sprawdzanie czy wszystkie wymagane pola zostały przekazane */
        $requiredFields = ['code', 'ean13', 'price', 'idCategory'];
        foreach ($requiredFields as $requiredField) {
            if(!isset($requestArray[$requiredField])) {
                return $this->json(["error" => "Nie przekazano wymaganego parametru '".$requiredField."'"]);
            }
        }
        if(!isset($requestArray['translations'])) {
            return $this->json(["error" => "Nie przekazano tłumaczeń"]);
        }else{
            if(count($requestArray['translations'] == 0)) {
                return $this->json(["error" => "Nie przekazano tłumaczeń"]);
            }else{
                /* @Todo: Sprawdzanie czy dany język istnieje w tabeli z językami jezeli nie to dodawanie go do tej tabli */
            }
        }
        /* Ustawianie podstawowych danych artykułu */
        $article = new Article();
        $article->setCode($requestArray['code']);
        $article->setEan13($requestArray['ean13']);
        $article->setPrice($requestArray['price']);
        $article->setIdCategory($requestArray['idCategory']);
        /* @Todo: Ustawianie tłumaczeń, przynajmniej jedno powinno być przekazane, w przypadku braku takiego języka w bazie danych tworzenie nowego  */

        $entityManager->persist($article);
        $entityManager->flush();

        $data =  [
            'id' => $article->getId(),
            'code' => $article->getCode()
        ];

        return $this->json($data);
    }
}
