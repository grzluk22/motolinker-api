<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticleLanguage;
use App\Repository\ArticleLanguageRepository;
use App\Repository\ArticleRepository;
use App\Repository\LanguageRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use function Symfony\Config\toArray;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Entity\Language;

class ArticleController extends AbstractController
{
    /**
     * Wyświetla liste artykułów
     *
     * Dodatkowy opis metody do NelmioApiDocBundle
     *
     * @OA\Response(
     *     response=200,
     *     description="successful operation",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(ref="../Entity/Article"),
     *     )
     * )
     *
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
     *
     *
     * @OA\RequestBody(
     *     request="ArticleCreateRequestBody",
     *     description="Artykuł",
     *     required=true,
     *     @OA\JsonContent(
     *        allOf={
     *           @OA\Schema(
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
     * )
     *        },
     *       example={
     *                         "code": "36790-SET-MS",
     *                         "ean13": "1234567890123",
     *                         "price": "367.99",
     *                         "idCategory": "0"
     *                     }
     *    )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Stworzono artykuł",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="id",
     *                         type="int",
     *                         description="Unikalne ID"
     *                     ),
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
     *                         "id": "1",
     *                         "code": "36790-SET-MS",
     *                         "ean13": "1234567890123",
     *                         "price": "367.99",
     *                         "idCategory": "0"
     *                     }
     *                 )
     *             )
     *         })
     * )
     * @OA\Response(
     *     response=409,
     *     description="Artykuł o podanym kodzie juz istnieje"
     * )
     **/
    #[Route('/article', name: 'app_article_create', methods: ["POST"])]
    public function create(ManagerRegistry $doctrine, LanguageRepository $languageRepository, ArticleRepository $articleRepository, ArticleLanguageRepository $articleLanguageRepository, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $languageManager = $doctrine->getManagerForClass(Language::class);
        $articleLanguageManager = $doctrine->getManagerForClass(ArticleLanguage::class);
        $requestArray = $request->toArray();
        /* Sprawdzanie czy artykuł o podanym kodzie nie istnieje juz w bazie danych */
        $articleResult = $articleRepository->findOneByCode($requestArray['code']);
        if($articleResult !== null) {
            return $this->json(["error" => "Artykuł o takim kodzie już istnieje"]);
        }
        /* Sprawdzanie czy wszystkie wymagane pola zostały przekazane */
        $requiredFields = ['code', 'ean13', 'price', 'idCategory'];
        foreach ($requiredFields as $requiredField) {
            if (!isset($requestArray[$requiredField])) {
                return $this->json(["error" => "Nie przekazano wymaganego parametru '" . $requiredField . "'"]);
            }
        }
        if (!isset($requestArray['translations'])) {
            return $this->json(["error" => "Nie przekazano tłumaczeń"]);
        } else {
            if (count($requestArray['translations']) == 0) {
                return $this->json(["error" => "Nie przekazano tłumaczeń"]);
            } else {
                /* Sprawdzanie czy dany język istnieje w tabeli z językami jezeli nie to dodawanie go do tej tabli */
                foreach ($requestArray['translations'] as $languageName=>$translation) {
                    $langResult = $languageRepository->findOneByName($languageName);
                    if($langResult === null) {
                        /* Wstawianie nowego języka */
                        $language = new Language();
                        $language->setName($languageName);
                        $language->setIsoCode($languageName);
                        $languageManager->persist($language);
                        $languageManager->flush();
                    }
                }
            }
        }

        /* Ustawianie podstawowych danych artykułu */
        $article = new Article();
        $article->setCode($requestArray['code']);
        $article->setEan13($requestArray['ean13']);
        $article->setPrice($requestArray['price']);
        $article->setIdCategory($requestArray['idCategory']);
        $entityManager->persist($article);
        $entityManager->flush();

        /* @Todo: Ustawianie tłumaczeń */
        /* Pobieranie id wszystkich dostępnych języków */
        $languages = $languageRepository->findAll();
        $languageIds = [];
        foreach ($languages as $language) {
            $languageIds[$language->getName()] = $language->getId();
        }
        foreach ($requestArray['translations'] as $languageName=>$translation) {
            $articleLanguage = new ArticleLanguage();
            $articleLanguage->setName($translation);
            $articleLanguage->setDescription('asd');
            $articleLanguage->setIdArticle($article->getId());
            $articleLanguage->setIdLanguage($languageIds[$languageName]);
            $articleLanguageManager->persist($articleLanguage);
            $articleLanguageManager->flush();
        }

        $data = [
            'id' => $article->getId(),
            'code' => $article->getCode(),
            'ean13' => $article->getEan13(),
            'price' => $article->getPrice(),
            'translations' => $articleLanguageRepository->findByArticleId($article->getId())
        ];

        return $this->json($data);
    }
}
