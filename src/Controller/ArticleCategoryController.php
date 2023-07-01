<?php

namespace App\Controller;

use App\Entity\ArticleCategory;
use App\Repository\ArticleCategoryRepository;
use App\Repository\ArticleLanguageRepository;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class ArticleCategoryController extends AbstractController
{
    /**
     * Wyświetla liste artykułów dla danej kategorii
     *
     * @OA\Tag(name="Category")
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
     *                         "id": 1,
     *                         "code": "36790-SET-MS",
     *                         "ean13": "1234567890123",
     *                         "price": "367.99",
     *                         "idCategory": 0,
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
    #[Route('/category/{id}/articles', name: 'app_category_articles', methods: ['GET'])]
    public function index(ArticleCategoryRepository $articleCategoryRepository,ArticleLanguageRepository $articleLanguageRepository, ArticleRepository $articleRepository, int $id): Response
    {
        $categoryArticles = $articleCategoryRepository->findBy(['id_category' => $id]);
        $data = [];
        foreach ($categoryArticles as $categoryArticle) {
            $data[] = $articleRepository->findOneBy(['id' => $categoryArticle->getIdArticle()]);
        }

        foreach ($data as $data_id=>$data_val) {
            $data[$data_id]->translations = $articleLanguageRepository->findBy(['id_article' => $data_val->getId()]);
        }
        return $this->json($data);
    }

    /**
     * Dodaje artykuł do kategorii
     *
     * @OA\Tag(name="Category")
     * @OA\Response(
     *     response=200,
     *     description="Dodano"
     * )
     * * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono artykułu/kategorii o podanym id"
     * )
     * * @OA\Response(
     *     response=302,
     *     description="Artykuł już przypisany do tej kategorii"
     * )
     *
     */
    #[Route('/category/{id_category}/article/{id_article}', name: 'app_category_article_delete_add', methods: ['POST'])]
    public function add(ManagerRegistry $managerRegistry, CategoryRepository $categoryRepository, ArticleCategoryRepository $articleCategoryRepository, ArticleRepository $articleRepository, int $id_category, int $id_article): Response
    {
        $article = $articleRepository->findOneBy(['id' => $id_article]);
        $category = $categoryRepository->findOneBy(['id' => $id_category]);
        if($article === null) return $this->json(['error' => "Artykuł o podanym id nie istnieje"], 404);
        if($category === null) return $this->json(['error' => "Kategoria o podanym id nie istnieje"], 404);

        $articleCategoryCheck = $articleCategoryRepository->findOneBy(['id_category' => $id_category, 'id_article' => $id_article]);
        $articleCategoryManager = $managerRegistry->getManagerForClass(ArticleCategory::class);
        if($articleCategoryCheck === null) {
            $articleCategory = new ArticleCategory();
            $articleCategory->setIdCategory($id_category);
            $articleCategory->setIdArticle($id_article);
            $articleCategoryManager->persist($articleCategory);;
            $articleCategoryManager->flush();
        }else{
            return $this->json(['error' => "Ten artykuł już jest przypisany do tej kategorii"]);
        }
        return $this->json($articleCategory);
    }

    /**
     * Usuwa artykuł z kategorii
     *
     * @OA\Tag(name="Category")
     * @OA\Response(
     *     response=200,
     *     description="Usunieto"
     * )
     * * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono artykułu/kategorii o podanym id"
     * )
     *
     */
    #[Route('/category/{id_category}/article/{id_article}', name: 'app_category_article_delete', methods: ['DELETE'])]
    public function delete(ManagerRegistry $managerRegistry, CategoryRepository $categoryRepository, ArticleCategoryRepository $articleCategoryRepository, ArticleRepository $articleRepository, int $id_category, int $id_article): Response
    {
        $article = $articleRepository->findOneBy(['id' => $id_article]);
        $category = $categoryRepository->findOneBy(['id' => $id_category]);
        if($article === null) return $this->json(['error' => "Artykuł o podanym id nie istnieje"]);
        if($category === null) return $this->json(['error' => "Kategoria o podanym id nie istnieje"]);

        $articleCategory = $articleCategoryRepository->findOneBy(['id_category' => $id_category, 'id_article' => $id_article]);
        $articleCategoryManager = $managerRegistry->getManagerForClass(ArticleCategory::class);
        $articleCategoryManager->remove($articleCategory);
        $articleCategoryManager->flush();
        return $this->json(['message' => "Usunięto"]);
    }
}
