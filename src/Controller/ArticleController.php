<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticleLanguage;
use App\Entity\ArticleEan;
use App\Repository\ArticleLanguageRepository;
use App\Repository\ArticleEanRepository;
use App\Repository\ArticleRepository;
use App\Repository\ImageRepository;
use App\Repository\LanguageRepository;
use App\Service\ImageUploadService;
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
     *                     example={
     *                         {
     *                             "id": 1,
     *                             "code": "36790-SET-MS",
     *                             "ean13": "1234567890123",
     *                             "ean13_list": {"1234567890123", "5901234123457"},
     *                             "price": 367.99,
     *                             "name":"Zestaw zawieszenia",
     *                             "description":"Zawieszenie do Audi A3",
     *                             "id_category": 0
     *                         }
     *                     }
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
    public function index(ArticleRepository $articleRepository, ImageRepository $imageRepository, ImageUploadService $imageUploadService): JsonResponse
    {
        $result = $articleRepository->findAll();
        if (empty($result)) {
            return new JsonResponse([]);
        }
        
        // Pobierz wszystkie główne zdjęcia w jednym zapytaniu
        $articleIds = array_map(fn($article) => $article->getId(), $result);
        $mainImages = $imageRepository->findMainImagesForArticles($articleIds);
        
        $withThumbnails = [];
        foreach ($result as $article) {
            $articleId = $article->getId();
            $mainImage = $mainImages[$articleId] ?? null;
            // Przekaż articleId bezpośrednio, aby uniknąć problemów z relacjami Doctrine
            $thumbnailUrl = $mainImage ? $imageUploadService->getThumbnailUrl($mainImage, $articleId) : null;
            
            // Ręcznie buduj tablicę z właściwości Article
            $articleArray = [
                'id' => $article->id,
                'code' => $article->code,
                'ean13' => $article->ean13,
                'price' => $article->price,
                'id_category' => $article->id_category,
                'name' => $article->name,
                'description' => $article->description,
                'thumbnail_url' => $thumbnailUrl
            ];
            $withThumbnails[] = (object)$articleArray;
        }
        return new JsonResponse($withThumbnails);
    }

    /**
     * Wyświetla przefiltrowana liste artykulow.
     *
     * @OA\Tag(name="Article")
     * @OA\RequestBody(
     * request="ArticleGetByRequestBody",
     * description="Filtry",
     * required=true,
     * @OA\JsonContent(
     * example={
     *     "criteria": {
     *         "code" :"36790-SET-MS",
     *         "ean13": "1234567890123",
     *         "price": 367.99,
     *         "id_category": 0,
     *         "name":"Zestaw zawieszenia",
     *         "description":"Zawieszenie do Audi A5 b6",
     *         "searchLike": true
     *     },
     *     "orderBy": {
     *         "id":"DESC"
     *     },
     *     "limit":20,
     *     "offset":40
     * }
     *   )
     *  )
     *
     * @OA\Response(
     *     response=200,
     *     description="Lista artykułów",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                         {
     *                             "id": 1,
     *                             "code": "36790-SET-MS",
     *                             "ean13": "1234567890123",
     *                             "ean13_list": {"1234567890123", "5901234123457"},
     *                             "price": 367.99,
     *                             "id_category": 0,
     *                             "name":"Zestaw zawieszenia",
     *                             "description":"Zawieszenie do Audi A5 b6"
     *                         }
     *                     }
     *             )
     *         })
     * )
     * * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono artykułu"
     * )
     *
     */
    #[Route('/article/get', name: 'app_article_get_by', methods: ["POST"])]
    public function getBy(ArticleRepository $articleRepository, ImageRepository $imageRepository, ImageUploadService $imageUploadService, Request $request = null): JsonResponse
    {
        /* Najprostsza metoda do pobrania artyklow przyjmuje obiekt i wyszukuje po jego polach w bazie danych */
        /* Jeżeli nie przekazano nic w body request to zwracanie wszystkich artykulow */
        /* Jezeli żaden aartkul nie pasuje do parametrów przekazanych w RequestBody lub nie ma nic w bazie to 404 */
        try {
            $requestArray = $request->toArray();
            $criteria = $requestArray['criteria'] ?? [];
            $orderBy = $requestArray['orderBy'] ?? [];
            $limit = $requestArray['limit'] ?? 50;
            $offset = $requestArray['offset'] ?? 0;
            /* Poprawka, like search powinno byc przekazane w requestArray a poprawnym przzepisaniem tych parametrow powinno zajac sie repozitory a*/
            $articles = $articleRepository->findByExtended($criteria, $orderBy, $limit, $offset);

        } catch (\Exception $exception) {
            if($exception->getMessage() == "Request body is empty.") {
                $articles = $articleRepository->findAll();
            }else{
                throw $exception;
            }
        }
        if(!$articles) return new JsonResponse(['message' => 'Nie znaleziono'], 404);
        
        // Pobierz wszystkie główne zdjęcia w jednym zapytaniu
        $articleIds = array_map(fn($article) => $article->getId(), $articles);
        $mainImages = $imageRepository->findMainImagesForArticles($articleIds);
        
        $withThumbnails = [];
        foreach ($articles as $article) {
            $articleId = $article->getId();
            $mainImage = $mainImages[$articleId] ?? null;
            // Przekaż articleId bezpośrednio, aby uniknąć problemów z relacjami Doctrine
            $thumbnailUrl = $mainImage ? $imageUploadService->getThumbnailUrl($mainImage, $articleId) : null;
            
            // Ręcznie buduj tablicę z właściwości Article
            $articleArray = [
                'id' => $article->id,
                'code' => $article->code,
                'ean13' => $article->ean13,
                'price' => $article->price,
                'id_category' => $article->id_category,
                'name' => $article->name,
                'description' => $article->description,
                'thumbnail_url' => $thumbnailUrl
            ];
            $withThumbnails[] = (object)$articleArray;
        }
        return new JsonResponse($withThumbnails);
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
     *                     example={
     *                         "id": 1,
     *                         "code": "36790-SET-MS",
     *                         "ean13": "1234567890123",
     *                         "ean13_list": {"1234567890123", "5901234123457"},
     *                         "price": 367.99,
     *                         "id_category": 0,
     *                         "name":"Zestaw zawieszenia",
     *                         "description":"Zawieszenie do Audi A3",
     *                         "translations": {
     *                              {
     *                                  "id": 1,
     *                                  "id_article": 1,
     *                                  "id_language": 1,
     *                                  "name": "PL nazwa",
     *                                  "description": "PL opis"
     *                              },
     *                              {
     *                                  "id": 2,
     *                                  "id_article": 1,
     *                                  "id_language": 2,
     *                                  "name": "EN name",
     *                                  "description": "EN description"
     *                              }
     *                         }
     *                     }
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
    public function getOne(ArticleRepository $articleRepository, ArticleLanguageRepository $articleLanguageRepository, ArticleEanRepository $articleEanRepository, int $id_article): JsonResponse
    {
        $article = $articleRepository->findOneBy(['id' => $id_article]);
        if(!$article) return new JsonResponse(["message" => 'Nie znaleziono produktu'], 404);
        $translations = $articleLanguageRepository->findByArticleId($article->getId());
        $eanList = array_map(fn($e) => $e->getEan13(), $articleEanRepository->findByArticleId($article->getId()));
        $data = (object) array_merge( (array)$article, array( 'translations' => $translations, 'ean13_list' => $eanList ) );
        return new JsonResponse($data);
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
     *                     example={
     *                         "code": "36790-SET-MS",
     *                         "ean13": "1234567890123",
     *                         "ean13_list": {"1234567890123", "5901234123457"},
     *                         "price": 367.99,
     *                         "id_category": 0,
     *                         "name": "Article name",
     *                         "description": "Article description",
     *                         "translations": {
     *                              {"id_language": 1, "name": "PL nazwa", "description": "PL opis"},
     *                              {"id_language": 2, "name": "EN name", "description": "EN description"}
     *                         }
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
     *                     example={
     *                         "id": 1,
     *                         "code": "36790-SET-MS",
     *                         "ean13": "1234567890123",
     *                         "ean13_list": {"1234567890123", "5901234123457"},
     *                         "price": 367.99,
     *                         "id_category": 0,
     *                         "name": "Article name",
     *                         "description": "Article description",
     *                         "translations": {
     *                              {"id": 1, "id_article": 1, "id_language": 1, "name": "PL nazwa", "description": "PL opis"},
     *                              {"id": 2, "id_article": 1, "id_language": 2, "name": "EN name", "description": "EN description"}
     *                         }
     *                     }
     *                 )
     *             )
     *         })
     * )
     * @OA\Response(
     *     response=400,
     *     description="Artykuł o podanym kodzie juz istnieje"
     * )
     **/
    #[Route('/article', name: 'app_article_create', methods: ["POST"])]
    public function create(ManagerRegistry $doctrine, LanguageRepository $languageRepository, ArticleRepository $articleRepository, ArticleLanguageRepository $articleLanguageRepository, ArticleEanRepository $articleEanRepository, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $languageManager = $doctrine->getManagerForClass(Language::class);
        $articleLanguageManager = $doctrine->getManagerForClass(ArticleLanguage::class);
        $articleEanManager = $doctrine->getManagerForClass(ArticleEan::class);
        $requestArray = $request->toArray();
        /* Sprawdzanie czy artykuł o podanym kodzie nie istnieje juz w bazie danych */
        $articleResult = $articleRepository->findOneByCode($requestArray['code']);
        if($articleResult !== null) {
            return new JsonResponse(["message" => "Artykuł o takim kodzie już istnieje"], 400);
        }
        /* Sprawdzanie czy wszystkie wymagane pola zostały przekazane */
        $requiredFields = ['code', 'price', 'id_category', 'name', 'description'];
        foreach ($requiredFields as $requiredField) {
            if (!isset($requestArray[$requiredField])) {
                return new JsonResponse(["message" => "Nie przekazano wymaganego parametru '" . $requiredField . "'"], 400);
            }
        }
        if (!isset($requestArray['ean13']) && !isset($requestArray['ean13_list'])) {
            return new JsonResponse(["message" => "Nie przekazano żadnego kodu EAN (ean13 lub ean13_list)"], 400);
        }

        /* Ustawianie podstawowych danych artykułu */
        $article = new Article();
        $article->setCode($requestArray['code']);
        // Przygotowanie listy EAN
        $eanList = [];
        if (isset($requestArray['ean13_list']) && is_array($requestArray['ean13_list'])) {
            $eanList = $requestArray['ean13_list'];
        }
        if (isset($requestArray['ean13'])) {
            $eanList[] = $requestArray['ean13'];
        }
        $eanList = array_values(array_unique(array_values(array_filter(array_map('strval', $eanList), fn($v) => trim($v) !== ''))));
        if (count($eanList) === 0) {
            return new JsonResponse(["message" => "Lista kodów EAN jest pusta"], 400);
        }
        $article->setEan13($eanList[0]);
        $article->setPrice($requestArray['price']);
        $article->setIdCategory($requestArray['id_category']);
        $article->setName($requestArray['name']);
        $article->setDescription($requestArray['description']);
        $entityManager->persist($article);
        $entityManager->flush();

        // Zapis listy EAN do tabeli article_ean
        foreach ($eanList as $ean) {
            $ae = new ArticleEan();
            $ae->setIdArticle($article->getId());
            $ae->setEan13($ean);
            $articleEanManager->persist($ae);
            $articleEanManager->flush();
        }

        /* Ustawianie tłumaczeń jezeli zotaly przekazane */
        if(isset($requestArray['translations'])) {
            foreach ($requestArray['translations'] as $translation) {
                /* Sprawdzanie czy język o podanym id już istnieje */
                $language = $languageRepository->findOneBy(['id' => $translation['id_language']]);
                if($language === null) {
                    return new JsonResponse(["message" => "Nie znaleziono języka o podanym id_language ".$translation['id_language']], 400);
                }
                $articleLanguage = new ArticleLanguage();
                $articleLanguage->setName($translation['name']);
                $articleLanguage->setDescription($translation['description']);
                $articleLanguage->setIdArticle($article->getId());
                $articleLanguage->setIdLanguage($translation['id_language']);
                $articleLanguageManager->persist($articleLanguage);
                $articleLanguageManager->flush();
            }
        }

        $data = (object)array_merge((array)$article, [
            "translations" => $articleLanguageRepository->findByArticleId($article->getId()),
            "ean13_list" => array_map(fn($e) => $e->getEan13(), $articleEanRepository->findByArticleId($article->getId()))
        ]);

        return new JsonResponse($data);
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
     *                     example={
     *                         "id": 1,
     *                         "code": "36790-SET-MS",
     *                         "ean13": "1234567890123",
     *                         "ean13_list": {"1234567890123", "5901234123457"},
     *                         "price": 367.99,
     *                         "id_category": 0,
     *                         "name": "Article name",
     *                         "description": "Article description",
     *                         "translations": {
     *                              {"id": 10, "id_language": 1, "name": "PL nazwa", "description": "PL opis"},
     *                              {"id": 11, "id_language": 2, "name": "EN name", "description": "EN description"}
     *                         }
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
     *                         "ean13_list": {"1234567890123", "5901234123457"},
     *                         "price": 367.99,
     *                         "id_category": 0,
     *                         "name": "Article name",
     *                         "description": "Article description",
     *                         "translations": {
     *                              {"id": 10, "id_article": 1, "id_language": 1, "name": "PL nazwa", "description": "PL opis"},
     *                              {"id": 11, "id_article": 1, "id_language": 2, "name": "EN name", "description": "EN description"}
     *                         }
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
    public function edit(ManagerRegistry $doctrine, LanguageRepository $languageRepository, ArticleRepository $articleRepository, ArticleLanguageRepository $articleLanguageRepository, ArticleEanRepository $articleEanRepository, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $articleLanguageManager = $doctrine->getManagerForClass(ArticleLanguage::class);
        $articleEanManager = $doctrine->getManagerForClass(ArticleEan::class);
        $requestArray = $request->toArray();
        $article = $articleRepository->findOneBy(['id' => $requestArray['id']]);
        if($article === null) {
            return new JsonResponse(["message" => "Nie znaleziono artykułu o podanym kodzie"], 404);
        }
        /* Ustawianie danych artykułu */
        $article->setCode($requestArray['code']);
        $article->setPrice($requestArray['price']);
        $article->setIdCategory($requestArray['id_category']);
        $article->setName($requestArray['name']);
        $article->setDescription($requestArray['description']);
        $entityManager->persist($article);
        $entityManager->flush();

        // Synchronizacja listy EAN jeśli przekazana
        if (isset($requestArray['ean13_list']) || isset($requestArray['ean13'])) {
            $eanList = [];
            if (isset($requestArray['ean13_list']) && is_array($requestArray['ean13_list'])) {
                $eanList = $requestArray['ean13_list'];
            }
            if (isset($requestArray['ean13'])) {
                $eanList[] = $requestArray['ean13'];
            }
            $eanList = array_values(array_unique(array_values(array_filter(array_map('strval', $eanList), fn($v) => trim($v) !== ''))));
            if (count($eanList) > 0) {
                // zaktualizuj główny ean
                $article->setEan13($eanList[0]);
                $entityManager->persist($article);
                $entityManager->flush();

                $existing = $articleEanRepository->findByArticleId($article->getId());
                $existingValues = array_map(fn($e) => $e->getEan13(), $existing);
                // usuń nieobecne
                foreach ($existing as $existingEan) {
                    if (!in_array($existingEan->getEan13(), $eanList, true)) {
                        $articleEanManager->remove($existingEan);
                        $articleEanManager->flush();
                    }
                }
                // dodaj nowe
                foreach ($eanList as $ean) {
                    if (!in_array($ean, $existingValues, true)) {
                        $ae = new ArticleEan();
                        $ae->setIdArticle($article->getId());
                        $ae->setEan13($ean);
                        $articleEanManager->persist($ae);
                        $articleEanManager->flush();
                    }
                }
            }
        }
        /* Ustawianie tłumaczeń jezeli zostaly przekazane */
        if(isset($requestArray['translations'])) {
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
        }


        $data = (object)array_merge((array)$article, [
            "translations" => $articleLanguageRepository->findByArticleId($article->getId()),
            "ean13_list" => array_map(fn($e) => $e->getEan13(), $articleEanRepository->findByArticleId($article->getId()))
        ]);

        return new JsonResponse($data);
    }

    /**
     * Usuwa istniejący artykuł
     *
     * @OA\Tag(name="Article")
     * @OA\Response(
     *     response=200,
     *     description="Usunięto",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"message": "Usunięto"}
     *                 )
     *             )
     *     }
     * )
     * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono artykułu o podanym kodzie"
     * )
     **/
    #[Route('/article/{id_article}', name: 'app_article_delete', methods: ["DELETE"])]
    public function delete(ManagerRegistry $doctrine, LanguageRepository $languageRepository, ArticleRepository $articleRepository, ArticleLanguageRepository $articleLanguageRepository, ArticleEanRepository $articleEanRepository, int $id_article): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $articleLanguageManager = $doctrine->getManagerForClass(ArticleLanguage::class);
        $articleEanManager = $doctrine->getManagerForClass(ArticleEan::class);
        $article = $articleRepository->findOneBy(['id' => $id_article]);
        if($article === null) {
            return new JsonResponse(["message" => "Nie znaleziono artykułu o podanym id"], 404);
        }
        /* Usuwanie tlumaczeń artykułu */
        $translations = $articleLanguageRepository->findBy(['id_article' => $article->getId()]);
        foreach ($translations as $translation) {
            $articleLanguageManager->remove($translation);
            $articleLanguageManager->flush();
        }
        /* Usuwanie eanów artykułu */
        $eans = $articleEanRepository->findBy(['id_article' => $article->getId()]);
        foreach ($eans as $ean) {
            $articleEanManager->remove($ean);
            $articleEanManager->flush();
        }
        /* Usuwanie samego artykułu */
        $entityManager->remove($article);
        $entityManager->flush();

        return new JsonResponse(["message" => "Usunięto"]);
    }
}
