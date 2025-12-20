<?php

namespace App\Controller;

use App\Entity\ArticleCategory;
use App\Repository\ArticleCategoryRepository;
use App\Repository\ArticleLanguageRepository;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

class ArticleCategoryController extends AbstractController
{
    /**
     * Wyświetla liste artykułów dla danej kategorii
     */
    #[OA\Tag(name: "Category")]
    #[OA\Response(
        response: 200,
        description: "Lista artykułów w danej kategorii",
        content: new OA\JsonContent(
            example: [
                "id" => 1,
                "code" => "36790-SET-MS",
                "ean13" => "1234567890123",
                "price" => "367.99",
                "id_category" => 0,
                "translations" => [
                    [
                        "id" => 1,
                        "id_article" => 1,
                        "id_language" => 1,
                        "name" => "New",
                        "description" => "asd"
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Brak artykułów"
    )]
    #[Route('/category/{id_category}/articles', name: 'app_category_articles', methods: ['GET'])]
    public function index(ArticleCategoryRepository $articleCategoryRepository,ArticleLanguageRepository $articleLanguageRepository, ArticleRepository $articleRepository, int $id_category): JsonResponse
    {
        $categoryArticles = $articleCategoryRepository->findBy(['id_category' => $id_category]);
        if(!$categoryArticles) return new JsonResponse(['message' => 'Nie znaeziono żadnych artykułów'], 404);
        $categoryArticlesDefault = $articleRepository->findBy(['id_category' => $id_category]);
        if(!$categoryArticlesDefault) return new JsonResponse(['message' => 'Nie znaeziono żadnych artykułów'], 404);
        $data = [];
        foreach ($categoryArticles as $categoryArticle) {
            $data[] = $articleRepository->findOneBy(['id' => $categoryArticle->getIdArticle()]);
        }
        $data = array_merge($data, $categoryArticlesDefault);

        foreach ($data as $data_id=>$data_val) {
            $data[$data_id]->translations = $articleLanguageRepository->findBy(['id_article' => $data_val->getId()]);
        }
        return new JsonResponse($data);
    }

    /**
     * Dodaje artykuł do kategorii
     */
    #[OA\Tag(name: "Category")]
    #[OA\Response(
        response: 200,
        description: "Dodano"
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono artykułu/kategorii o podanym id"
    )]
    #[OA\Response(
        response: 400,
        description: "Artykuł już przypisany do tej kategorii"
    )]
    #[Route('/category/{id_category}/article/{id_article}', name: 'app_category_article_delete_add', methods: ['POST'])]
    public function add(ManagerRegistry $managerRegistry, CategoryRepository $categoryRepository, ArticleCategoryRepository $articleCategoryRepository, ArticleRepository $articleRepository, int $id_category, int $id_article): JsonResponse
    {
        $article = $articleRepository->findOneBy(['id' => $id_article]);
        $category = $categoryRepository->findOneBy(['id' => $id_category]);
        if($article === null) return new JsonResponse(['message' => "Artykuł o podanym id nie istnieje"], 404);
        if($category === null) return new JsonResponse(['message' => "Kategoria o podanym id nie istnieje"], 404);

        $articleCategoryCheck = $articleCategoryRepository->findOneBy(['id_category' => $id_category, 'id_article' => $id_article]);
        $articleCategoryManager = $managerRegistry->getManagerForClass(ArticleCategory::class);
        if($articleCategoryCheck === null) {
            $articleCategory = new ArticleCategory();
            $articleCategory->setIdCategory($id_category);
            $articleCategory->setIdArticle($id_article);
            $articleCategoryManager->persist($articleCategory);;
            $articleCategoryManager->flush();
        }else{
            return new JsonResponse(['message' => "Ten artykuł już jest przypisany do tej kategorii"], 400);
        }
        return $this->json($articleCategory);
    }

    /**
     * Usuwa artykuł z kategorii
     */
    #[OA\Tag(name: "Category")]
    #[OA\Response(
        response: 200,
        description: "Usunieto"
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono artykułu/kategorii o podanym id"
    )]
    #[Route('/category/{id_category}/article/{id_article}', name: 'app_category_article_delete', methods: ['DELETE'])]
    public function delete(ManagerRegistry $managerRegistry, CategoryRepository $categoryRepository, ArticleCategoryRepository $articleCategoryRepository, ArticleRepository $articleRepository, int $id_category, int $id_article): JsonResponse
    {
        $article = $articleRepository->findOneBy(['id' => $id_article]);
        if($article === null) return new JsonResponse(['message' => "Artykuł o podanym id nie istnieje"], 404);
        $category = $categoryRepository->findOneBy(['id' => $id_category]);
        if($category === null) return new JsonResponse(['message' => "Kategoria o podanym id nie istnieje"], 404);

        $articleCategory = $articleCategoryRepository->findOneBy(['id_category' => $id_category, 'id_article' => $id_article]);
        $articleCategoryManager = $managerRegistry->getManagerForClass(ArticleCategory::class);
        $articleCategoryManager->remove($articleCategory);
        $articleCategoryManager->flush();
        return new JsonResponse(['message' => "Usunięto"]);
    }

    /**
     * Zwraca listę kategorii przypisanych do artykułu.
     */
    #[OA\Tag(name: "ArticleCategory")]
    #[OA\Response(
        response: 200,
        description: "Lista kategorii artykułu"
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono artykułu o podanym id"
    )]
    #[Route('/article/{id}/category', name: 'app_article_categories_list', methods: ['GET'])]
    public function getArticleCategories(
        int $id,
        ArticleRepository $articleRepository,
        ArticleCategoryRepository $articleCategoryRepository,
        CategoryRepository $categoryRepository
    ): JsonResponse {
        $article = $articleRepository->find($id);
        if ($article === null) {
            return new JsonResponse(['message' => 'Nie znaleziono artykułu o podanym id'], 404);
        }

        $categoryIds = [];
        $defaultCategoryId = $article->getIdCategory();
        if ($defaultCategoryId !== null) {
            $categoryIds[] = $defaultCategoryId;
        }

        $additionalCategories = $articleCategoryRepository->findBy(['id_article' => $id]);
        foreach ($additionalCategories as $link) {
            $categoryIds[] = $link->getIdCategory();
        }

        $categoryIds = array_values(array_unique(array_filter($categoryIds, static fn ($value) => $value !== null)));

        if (count($categoryIds) === 0) {
            return new JsonResponse([]);
        }

        $categories = $categoryRepository->findBy(['id' => $categoryIds]);
        $categoriesById = [];
        foreach ($categories as $category) {
            $categoriesById[$category->getId()] = [
                'id' => $category->getId(),
                'id_parent' => $category->getIdParent()
            ];
        }

        $orderedCategories = [];
        foreach ($categoryIds as $categoryId) {
            if (isset($categoriesById[$categoryId])) {
                $orderedCategories[] = $categoriesById[$categoryId];
            }
        }

        return new JsonResponse($orderedCategories);
    }
}
