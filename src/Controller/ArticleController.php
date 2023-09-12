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
     *                              property="id_language",
     *                              type="int",
     *                              description="id języka"
     *                          ),
     *                          @OA\Property(
     *                              property="name",
     *                              type="int",
     *                              description="Nazwa"
     *                          ),
     *                          @OA\Property(
     *                              property="description",
     *                              type="int",
     *                              description="Opis"
     *                          ),
     *                      )
     *
     *                     ),
     * )
     *        },
     *                     example={
     *                         "code": "36790-SET-MS",
     *                         "ean13": "1234567890123",
     *                         "price": "367.99",
     *                         "id_category": 0,
     *                              "translations": {
     *                               "id_language": 1,
     *                               "name": "Article name",
     *                               "description": "Article Description"
     *                          }
     *                     }
     *    )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Stworzony artykuł",
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
     *                               "name": "Article name",
     *                               "description": "Article description"
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
        $requiredFields = ['code', 'ean13', 'price', 'id_category'];
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
            }
        }

        /* Ustawianie podstawowych danych artykułu */
        $article = new Article();
        $article->setCode($requestArray['code']);
        $article->setEan13($requestArray['ean13']);
        $article->setPrice($requestArray['price']);
        $article->setIdCategory($requestArray['id_category']);
        $entityManager->persist($article);
        $entityManager->flush();

        /* Ustawianie tłumaczeń */
        foreach ($requestArray['translations'] as $translation) {
            $articleLanguage = new ArticleLanguage();
            $articleLanguage->setName($translation['name']);
            $articleLanguage->setDescription($translation['description']);
            $articleLanguage->setIdArticle($article->getId());
            $articleLanguage->setIdLanguage($translation['id_language']);
            $articleLanguageManager->persist($articleLanguage);
            $articleLanguageManager->flush();
        }

        $data = (object)array_merge((array)$article, ["translations" => $articleLanguageRepository->findByArticleId($article->getId())]);

        return $this->json($data);
    }

    /**
     * Edytuje istniejący artykuł
     *
     * Jeżeli chcesz dodać nowe tłumaczenie do artykułu możesz również skorzystać z tej metody. Poprostu w tabeli z tłumaczeniami przekaż jedno bez id, a zostanie ono automatycznie utworzone
     *
     *
     * @OA\Tag(name="Article")
     * @OA\RequestBody(
     *     request="ArticleUpdateRequestBody",
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
     * )
     *        },
     *                     example={
     *                         "code": "36790-SET-MS",
     *                         "ean13": "1234567890123",
     *                         "price": "367.99",
     *                         "id_category": 0,
     *                              "translations": {
     *                               "id_language": 1,
     *                               "name": "Article name",
     *                               "description": "Article Description"
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
     *                               "name": "Article name",
     *                               "description": "Article description"
     *                          }
     *                     }
     *             )
     *         })
     * )
     * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono artykułu o podanym id"
     * )
     **/
    #[Route('/article', name: 'app_article_edit', methods: ["PUT"])]
    public function edit(ManagerRegistry $doctrine, LanguageRepository $languageRepository, ArticleRepository $articleRepository, ArticleLanguageRepository $articleLanguageRepository, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $articleLanguageManager = $doctrine->getManagerForClass(ArticleLanguage::class);
        $requestArray = $request->toArray();
        $article = $articleRepository->findOneBy(['id' => $requestArray['id']]);
        if($article === null) {
            return $this->json(["error" => "Nie znaleziono artykułu o podanym kodzie"]);
        }
        /* Ustawianie danych artykułu */
        $article->setCode($requestArray['code']);
        $article->setEan13($requestArray['ean13']);
        $article->setPrice($requestArray['price']);
        $article->setIdCategory($requestArray['id_category']);
        $entityManager->persist($article);
        $entityManager->flush();
        /* Ustawianie tłumaczeń */
        foreach ($requestArray['translations'] as $translation) {
            if(!isset($translation['id'])) {
                /* Nie podano id istniejącego tłumaczenia więc tworzymy nowe, uznając że użytkownik chce stworzyć nowe tłumaczenie dla tego artykułu */
                $articleLanguage = new ArticleLanguage();
                $articleLanguage->setIdArticle($article->getId());
                $articleLanguage->setIdLanguage($translation['id_language']);
            }else{
                $articleLanguage = $articleLanguageRepository->findOneBy(['id' => $translation['id']]);
            }
            $articleLanguage->setName($translation['name']);
            $articleLanguage->setDescription($translation['description']);
            $articleLanguageManager->persist($articleLanguage);
            $articleLanguageManager->flush();
        }

        $data = (object)array_merge((array)$article, ["translations" => $articleLanguageRepository->findByArticleId($article->getId())]);

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
    #[Route('/article/{id_article}', name: 'app_article_delete', methods: ["DELETE"])]
    public function delete(ManagerRegistry $doctrine, LanguageRepository $languageRepository, ArticleRepository $articleRepository, ArticleLanguageRepository $articleLanguageRepository, int $id_article): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $articleLanguageManager = $doctrine->getManagerForClass(ArticleLanguage::class);
        $article = $articleRepository->findOneBy(['id' => $id_article]);
        if($article === null) {
            return $this->json(["error" => "Nie znaleziono artykułu o podanym id"]);
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
