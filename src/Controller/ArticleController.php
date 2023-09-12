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
     * @OA\Tag(name="Article")
     * @OA\Response(
     *     response=200,
     *     description="Lista artykułów",
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
     *                         property="id_category",
     *                         type="integer",
     *                         description="Domyślne id kategorii",
     *                     ),
     *                     @OA\Property(
     *                         property="translations",
     *                         type="array",
     *                         description="Tablica tłumaczeń",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="id",
     *                              type="int",
     *                              description="id języka"
     *                          ),
     *                          @OA\Property(
     *                              property="id_article",
     *                              type="int",
     *                              description="id języka"
     *                          ),
     *                          @OA\Property(
     *                              property="id_language",
     *                              type="int",
     *                              description="id języka"
     *                          ),
     *                          @OA\Property(
     *                              property="name",
     *                              type="int",
     *                              description="id języka"
     *                          ),
     *                          @OA\Property(
     *                              property="description",
     *                              type="int",
     *                              description="id języka"
     *                          ),
     *                      )
     *
     *                     ),
     *                     example={
     *                         "id": 1,
     *                         "code": "36790-SET-MS",
     *                         "ean13": "1234567890123",
     *                         "price": "367.99",
     *                         "id_category": 0,
     *                              "translations": {
     *                               "id": 1,
     *                               "id_article": 1,
     *                               "id_language": 1,
     *                               "name": "New",
     *                               "description": "asd"
     *                          }
     *                     }
     *                 )
     *             )
     *         })
     * )
     * * @OA\Response(
     *     response=404,
     *     description="Brak artykułów"
     * )
     *
     */
    #[Route('/article', name: 'app_article_get', methods: ["GET"])]
    public function index(ArticleRepository $articleRepository, ArticleLanguageRepository $articleLanguageRepository): JsonResponse
    {
        $result = $articleRepository->findAll();
        foreach ($result as $resid=>$res) {
            $result[$resid]->translations = $articleLanguageRepository->findByArticleId($res->getId());
        }
        return $this->json($result);
    }

    /**
     * Wyświetla konkretny artykuł
     *
     * @OA\Tag(name="Article")
     * @OA\Response(
     *     response=200,
     *     description="Lista artykułów",
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
     *                         property="id_category",
     *                         type="integer",
     *                         description="Domyślne id kategorii",
     *                     ),
     *                     @OA\Property(
     *                         property="translations",
     *                         type="array",
     *                         description="Tablica tłumaczeń",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="id",
     *                              type="int",
     *                              description="id języka"
     *                          ),
     *                          @OA\Property(
     *                              property="id_article",
     *                              type="int",
     *                              description="id języka"
     *                          ),
     *                          @OA\Property(
     *                              property="id_language",
     *                              type="int",
     *                              description="id języka"
     *                          ),
     *                          @OA\Property(
     *                              property="name",
     *                              type="int",
     *                              description="id języka"
     *                          ),
     *                          @OA\Property(
     *                              property="description",
     *                              type="int",
     *                              description="id języka"
     *                          ),
     *                      )
     *
     *                     ),
     *                     example={
     *                         "id": 1,
     *                         "code": "36790-SET-MS",
     *                         "ean13": "1234567890123",
     *                         "price": "367.99",
     *                         "id_category": 0,
     *                              "translations": {
     *                               "id": 1,
     *                               "id_article": 1,
     *                               "id_language": 1,
     *                               "name": "New",
     *                               "description": "asd"
     *                          }
     *                     }
     *                 )
     *             )
     *         })
     * )
     * * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono artykułu"
     * )
     *
     */
    #[Route('/article/{id_article}', name: 'app_article_get_one', methods: ["GET"])]
    public function getOne(ArticleRepository $articleRepository, ArticleLanguageRepository $articleLanguageRepository, int $id_article): JsonResponse
    {
        $article = $articleRepository->findOneBy(['id' => $id_article]);
        $translations = $articleLanguageRepository->findByArticleId($article->getId());
        $data = (object) array_merge( (array)$article, array( 'translations' => $translations ) );

        return $this->json($data);
    }

    /**
     * Tworzy nowy artykuł
     *
     *
     * @OA\Tag(name="Article")
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
     *                     @OA\Property(
     *                         property="translations",
     *                         type="array",
     *                         description="Tablica tłumaczeń",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="pl",
     *                              type="string",
     *                              description="tłumaczenie_pl"
     *                          ))
     *
     *                     ),
     * )
     *        },
     *       example={
     *                         "code": "36790-SET-MS",
     *                         "ean13": "1234567890123",
     *                         "price": "367.99",
     *                         "idCategory": "0",
     *                         "translations": {
    *                               "pl": "polska nazwa"
     *                          }
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
     *                     @OA\Property(
     *                         property="translations",
     *                         type="array",
     *                         description="Tablica tłumaczeń",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="pl",
     *                              type="string",
     *                              description="tłumaczenie_pl"
     *                          ))
     *
     *                     ),
     *                     example={
     *                         "id": "1",
     *                         "code": "36790-SET-MS",
     *                         "ean13": "1234567890123",
     *                         "price": "367.99",
     *                         "idCategory": "0",
     *                              "translations": {
     *                               "pl": "polska nazwa"
     *                          }
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

        /* Ustawianie tłumaczeń */
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

    /**
     * Edytuje istniejący artykuł
     *
     *
     *
     * @OA\Tag(name="Article")
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
     *                     @OA\Property(
     *                         property="translations",
     *                         type="array",
     *                         description="Tablica tłumaczeń",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="pl",
     *                              type="string",
     *                              description="tłumaczenie_pl"
     *                          ))
     *
     *                     ),
     * )
     *        },
     *       example={
     *                         "code": "36790-SET-MS",
     *                         "ean13": "1234567890123",
     *                         "price": "367.99",
     *                         "idCategory": "0",
     *                         "translations": {
     *                               "pl": "polska nazwa"
     *                          }
     *                     }
     *    )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Zaktualizowany artykuł",
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
     *                     @OA\Property(
     *                         property="translations",
     *                         type="array",
     *                         description="Tablica tłumaczeń",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="pl",
     *                              type="string",
     *                              description="tłumaczenie_pl"
     *                          ))
     *
     *                     ),
     *                     example={
     *                         "id": "1",
     *                         "code": "36790-SET-MS",
     *                         "ean13": "1234567890123",
     *                         "price": "367.99",
     *                         "idCategory": "0",
     *                              "translations": {
     *                               "pl": "polska nazwa"
     *                          }
     *                     }
     *                 )
     *             )
     *         })
     * )
     * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono artykułu o podanym kodzie"
     * )
     **/
    #[Route('/article', name: 'app_article_edit', methods: ["PUT"])]
    public function edit(ManagerRegistry $doctrine, LanguageRepository $languageRepository, ArticleRepository $articleRepository, ArticleLanguageRepository $articleLanguageRepository, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $articleLanguageManager = $doctrine->getManagerForClass(ArticleLanguage::class);
        $requestArray = $request->toArray();
        $article = $articleRepository->findOneByCode($requestArray['code']);
        if($article === null) {
            return $this->json(["error" => "Nie znaleziono artykułu o podanym kodzie"]);
        }
        /* Ustawianie danych artykułu */
        $article->setCode($requestArray['code']);
        $article->setEan13($requestArray['ean13']);
        $article->setPrice($requestArray['price']);
        $article->setIdCategory($requestArray['idCategory']);
        $entityManager->persist($article);
        $entityManager->flush();
        /* Ustawianie tłumaczeń */
        foreach ($requestArray['translations'] as $languageName=>$translation) {
            $language = $languageRepository->findOneBy(['name' => $languageName]);
            $articleLanguage = $articleLanguageRepository->findOneBy(['id_article' => $article->getId(), 'id_language' => $language->getId()]);
            if($articleLanguage === null) {
                $articleLanguage = new ArticleLanguage();
                $articleLanguage->setIdArticle($article->getId());
                $articleLanguage->setIdLanguage($language->getId());
            }
            $articleLanguage->setName($translation);
            $articleLanguage->setDescription('asd');
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

    /**
     * Usuwa istniejący artykuł
     *
     * @OA\Tag(name="Article")
     * @OA\Response(
     *     response=200,
     *     description="Usunięto"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono artykułu o podanym kodzie"
     * )
     **/
    #[Route('/article/{code}', name: 'app_article_delete', methods: ["DELETE"])]
    public function delete(ManagerRegistry $doctrine, LanguageRepository $languageRepository, ArticleRepository $articleRepository, ArticleLanguageRepository $articleLanguageRepository, string $code): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $articleLanguageManager = $doctrine->getManagerForClass(ArticleLanguage::class);
        $article = $articleRepository->findOneByCode($code);
        if($article === null) {
            return $this->json(["error" => "Nie znaleziono artykułu o podanym kodzie"]);
        }
        /* Usuwanie tlumaczeń artykułu */
        $translations = $articleLanguageRepository->findBy(['id_article' => $article->getId()]);
        foreach ($translations as $translation) {
            $articleLanguageManager->remove($translation);
            $articleLanguageManager->flush();
        }
        /* Usuwanie samego artykułu */
        $entityManager->remove($article);
        $entityManager->flush();

        return $this->json("Usunięto");
    }
}
