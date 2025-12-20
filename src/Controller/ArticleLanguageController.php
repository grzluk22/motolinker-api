<?php

namespace App\Controller;

use App\Entity\ArticleLanguage;
use App\Repository\ArticleLanguageRepository;
use App\Repository\ArticleRepository;
use App\Repository\LanguageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use App\HttpRequestModel\ArticleLanguageCreateRequest;
use App\HttpRequestModel\ArticleLanguageUpdateRequest;
use App\HttpResponseModel\MessageResponse;

class ArticleLanguageController extends AbstractController
{
    /**
     * Lista tłumaczeń wskazanego artykułu.
     */
    #[OA\Tag(name: "ArticleLanguage")]
    #[OA\Response(
        response: 200,
        description: "Lista tłumaczeń artykułu",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ArticleLanguage::class))
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono tłumaczeń dla artykułu"
    )]
    #[Route('/article/{id_article}/language', name: 'app_article_language_list', methods: ['GET'])]
    public function list(ArticleLanguageRepository $articleLanguageRepository, int $id_article): JsonResponse
    {
        $translations = $articleLanguageRepository->findBy(['id_article' => $id_article]);
        if (!$translations) {
            return new JsonResponse(['message' => 'Nie znaleziono tłumaczeń dla tego artykułu'], 404);
        }

        return new JsonResponse($translations);
    }

    /**
     * Tworzy tłumaczenie artykułu.
     */
    #[OA\Tag(name: "ArticleLanguage")]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["id_language", "name", "description"],
            properties: [
                new OA\Property(property: "id_language", type: "integer", example: 1),
                new OA\Property(property: "name", type: "string", example: "Komplet hamulcowy"),
                new OA\Property(property: "description", type: "string", example: "Opis produktu w wybranym języku")
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Dodano tłumaczenie"
    )]
    #[OA\Response(
        response: 400,
        description: "Błędne dane wejściowe lub tłumaczenie istnieje"
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono artykułu lub języka"
    )]
    #[Route('/article/{id_article}/language', name: 'app_article_language_create', methods: ['POST'])]
    public function create(
        int $id_article,
        Request $request,
        ArticleRepository $articleRepository,
        LanguageRepository $languageRepository,
        ArticleLanguageRepository $articleLanguageRepository
    ): JsonResponse {
        $payload = $request->toArray();
        $languageId = $payload['id_language'] ?? null;
        $name = $payload['name'] ?? null;
        $description = $payload['description'] ?? null;

        if ($languageId === null || $name === null || $description === null) {
            return new JsonResponse(['message' => 'Wymagane pola: id_language, name, description'], 400);
        }

        $article = $articleRepository->find($id_article);
        if ($article === null) {
            return new JsonResponse(['message' => 'Nie znaleziono artykułu o podanym id'], 404);
        }

        $language = $languageRepository->find($languageId);
        if ($language === null) {
            return new JsonResponse(['message' => 'Nie znaleziono języka o podanym id'], 404);
        }

        $existingTranslation = $articleLanguageRepository->findOneBy([
            'id_article' => $id_article,
            'id_language' => $languageId
        ]);

        if ($existingTranslation !== null) {
            return new JsonResponse(['message' => 'Tłumaczenie dla tego języka już istnieje'], 400);
        }

        $articleLanguage = new ArticleLanguage();
        $articleLanguage->setIdArticle($id_article);
        $articleLanguage->setIdLanguage($languageId);
        $articleLanguage->setName($name);
        $articleLanguage->setDescription($description);

        $articleLanguageRepository->save($articleLanguage, true);

        return new JsonResponse($articleLanguage, 201);
    }

    /**
     * Aktualizuje tłumaczenie artykułu.
     */
    #[OA\Tag(name: "ArticleLanguage")]
    #[OA\RequestBody(
        required: true,
        content: new Model(type: ArticleLanguageUpdateRequest::class)
    )]
    #[OA\Response(
        response: 200,
        description: "Zaktualizowano tłumaczenie",
        content: new Model(type: ArticleLanguage::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono tłumaczenia o podanym id"
    )]
    #[Route('/article/language/{id}', name: 'app_article_language_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        ArticleLanguageRepository $articleLanguageRepository
    ): JsonResponse {
        $articleLanguage = $articleLanguageRepository->find($id);
        if ($articleLanguage === null) {
            return new JsonResponse(['message' => 'Nie znaleziono tłumaczenia o podanym id'], 404);
        }

        $payload = $request->toArray();

        if (isset($payload['name'])) {
            $articleLanguage->setName($payload['name']);
        }

        if (isset($payload['description'])) {
            $articleLanguage->setDescription($payload['description']);
        }

        $articleLanguageRepository->save($articleLanguage, true);

        return new JsonResponse($articleLanguage);
    }

    /**
     * Usuwa tłumaczenie dla artykułu
     */
    #[OA\Tag(name: "ArticleLanguage")]
    #[OA\Response(
        response: 200,
        description: "Usunięto",
        content: new Model(type: MessageResponse::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono tłumaczenia o podanym id"
    )]
    #[Route('/article/language/{id}', name: 'app_article_language_delete', methods: ['DELETE'])]
    public function delete(ArticleLanguageRepository $articleLanguageRepository, string $id)
    {
        $articleLanguage = $articleLanguageRepository->findOneBy(['id' => $id]);
        if($articleLanguage === null) {
            return new JsonResponse(["message" => "Nie znaleziono tłumaczenia o podanym kodzie"], 404);
        }
        $articleLanguageRepository->remove($articleLanguage, true);
        return new JsonResponse(["message" => "Usunięto"]);
    }
}
