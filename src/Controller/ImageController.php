<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Image;
use App\Repository\ArticleRepository;
use App\Repository\ImageRepository;
use App\Service\ImageUploadService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

class ImageController extends AbstractController
{
    /**
     * Wyświetla listę obrazków dla danego produktu
     */
    #[OA\Tag(name: "ArticleImage")]
    #[OA\Response(
        response: 200,
        description: "Lista obrazków dla danego produktu",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "id_article", type: "integer", example: 1),
                    new OA\Property(property: "position", type: "integer", example: 1),
                    new OA\Property(property: "url", type: "string", example: "/uploads/articles/1/abc123.jpg"),
                    new OA\Property(property: "thumbnail_url", type: "string", example: "/uploads/articles/1/thumbnails/abc123.jpg"),
                    new OA\Property(property: "is_main", type: "boolean", example: true),
                    new OA\Property(property: "width", type: "integer", example: 1920),
                    new OA\Property(property: "height", type: "integer", example: 1080),
                    new OA\Property(property: "file_size", type: "integer", example: 524288)
                ]
            )
        )
    )]
    #[Route('/article/{id_article}/images', name: 'app_article_image_get', methods: ['GET'])]
    public function index(ImageRepository $imageRepository, ImageUploadService $imageUploadService, string $id_article): JsonResponse
    {
        $images = $imageRepository->findByArticleOrdered((int)$id_article);
        
        $result = array_map(function (Image $image) use ($imageUploadService) {
            return [
                'id' => $image->getId(),
                'id_article' => $image->getIdArticle(),
                'position' => $image->getPosition(),
                'url' => $image->getUrl(),
                'thumbnail_url' => $imageUploadService->getThumbnailUrl($image),
                'is_main' => $image->isMain(),
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
                'file_size' => $image->getFileSize(),
                'filename' => $image->getFilename(),
                'original_filename' => $image->getOriginalFilename(),
                'mime_type' => $image->getMimeType(),
                'created_at' => $image->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $images);

        return new JsonResponse($result);
    }

    /**
     * Wgrywa obrazek dla danego artykułu
     */
    #[OA\Tag(name: "ArticleImage")]
    #[OA\RequestBody(
        required: true,
        description: "Plik obrazu do wgrania",
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                type: "object",
                required: ["file"],
                properties: [
                    new OA\Property(
                        property: "file",
                        type: "string",
                        format: "binary",
                        description: "Plik obrazu (JPG, PNG, WebP, max 10MB)"
                    ),
                    new OA\Property(
                        property: "position",
                        type: "integer",
                        description: "Pozycja obrazu (opcjonalne, domyślnie na końcu)"
                    ),
                    new OA\Property(
                        property: "is_main",
                        type: "boolean",
                        description: "Czy to główne zdjęcie (opcjonalne, domyślnie false)"
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Dodany obrazek artykułu",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "id_article", type: "integer", example: 1),
                new OA\Property(property: "position", type: "integer", example: 1),
                new OA\Property(property: "url", type: "string", example: "/uploads/articles/1/abc123.jpg"),
                new OA\Property(property: "thumbnail_url", type: "string", example: "/uploads/articles/1/thumbnails/abc123.jpg")
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Błąd walidacji pliku"
    )]
    #[OA\Response(
        response: 404,
        description: "Artykuł nie istnieje"
    )]
    #[Route('/article/{id_article}/image', name: 'app_article_image_post', methods: ['POST'])]
    public function post(
        ManagerRegistry $doctrine,
        ArticleRepository $articleRepository,
        ImageRepository $imageRepository,
        ImageUploadService $imageUploadService,
        string $id_article,
        Request $request
    ): JsonResponse {
        // Check if article exists
        $article = $articleRepository->find((int)$id_article);
        if (!$article) {
            return new JsonResponse(['error' => 'Artykuł o podanym ID nie istnieje'], 404);
        }

        // Get uploaded file
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return new JsonResponse(['error' => 'Brak pliku do wgrania'], 400);
        }

        try {
            // Get optional parameters
            $position = $request->request->get('position');
            $isMain = filter_var($request->request->get('is_main', false), FILTER_VALIDATE_BOOLEAN);

            // If position not provided, set to max + 1
            if ($position === null) {
                $position = $imageRepository->getMaxPositionForArticle((int)$id_article) + 1;
            }

            // If setting as main, unset other main images
            if ($isMain) {
                $entityManager = $doctrine->getManager();
                $existingImages = $imageRepository->findByArticleOrdered((int)$id_article);
                foreach ($existingImages as $existingImage) {
                    if ($existingImage->isMain()) {
                        $existingImage->setIsMain(false);
                        $entityManager->persist($existingImage);
                    }
                }
                $entityManager->flush();
            }

            // Upload image
            $image = $imageUploadService->upload($file, $article, (int)$position, $isMain);

            // Save to database
            $entityManager = $doctrine->getManager();
            $entityManager->persist($image);
            $entityManager->flush();

            return new JsonResponse([
                'id' => $image->getId(),
                'id_article' => $image->getIdArticle(),
                'position' => $image->getPosition(),
                'url' => $image->getUrl(),
                'thumbnail_url' => $imageUploadService->getThumbnailUrl($image),
                'is_main' => $image->isMain(),
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
                'file_size' => $image->getFileSize(),
            ], 201);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Usuwa obrazek z produktu (plik i rekord w bazie)
     */
    #[OA\Tag(name: "ArticleImage")]
    #[OA\Response(
        response: 200,
        description: "Usunięto",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "Usunięto")
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono obrazka o podanym id"
    )]
    #[Route('/article/image/{id}', name: 'app_article_image_delete', methods: ['DELETE'])]
    public function delete(
        ManagerRegistry $doctrine,
        ImageRepository $imageRepository,
        ImageUploadService $imageUploadService,
        string $id
    ): JsonResponse {
        $entityManager = $doctrine->getManager();
        $image = $imageRepository->find((int)$id);
        
        if (!$image) {
            return new JsonResponse(['error' => 'Nie znaleziono obrazka o podanym id'], 404);
        }

        $articleId = $image->getIdArticle();
        $wasMain = $image->isMain();

        // Delete file from disk
        $imageUploadService->delete($image);

        // Delete from database
        $entityManager->remove($image);
        $entityManager->flush();

        // If deleted was main image, set first image as main
        if ($wasMain && $articleId) {
            $remainingImages = $imageRepository->findByArticleOrdered($articleId);
            if (!empty($remainingImages)) {
                $firstImage = $remainingImages[0];
                $firstImage->setIsMain(true);
                $entityManager->persist($firstImage);
                $entityManager->flush();
            }
        }

        return new JsonResponse(['message' => 'Usunięto']);
    }

    /**
     * Aktualizuje kolejność zdjęć dla artykułu
     */
    #[OA\Tag(name: "ArticleImage")]
    #[OA\RequestBody(
        required: true,
        description: "Tablica z id i pozycjami zdjęć",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(
                    property: "images",
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "position", type: "integer", example: 1)
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Kolejność zaktualizowana",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "Kolejność zaktualizowana")
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Nieprawidłowy format danych"
    )]
    #[Route('/article/{id_article}/images/reorder', name: 'app_article_image_reorder', methods: ['PUT'])]
    public function reorder(
        ImageRepository $imageRepository,
        string $id_article,
        Request $request
    ): JsonResponse {
        $requestArray = $request->toArray();

        if (!isset($requestArray['images']) || !is_array($requestArray['images'])) {
            return new JsonResponse(['error' => 'Nieprawidłowy format danych'], 400);
        }

        // Verify all images belong to the article
        foreach ($requestArray['images'] as $imageData) {
            if (!isset($imageData['id']) || !isset($imageData['position'])) {
                continue;
            }

            $image = $imageRepository->find($imageData['id']);
            if ($image && $image->getIdArticle() != (int)$id_article) {
                return new JsonResponse(['error' => 'Zdjęcie nie należy do tego artykułu'], 400);
            }
        }

        $imageRepository->updatePositions($requestArray['images']);

        return new JsonResponse(['message' => 'Kolejność zaktualizowana']);
    }

    /**
     * Ustawia zdjęcie jako główne
     */
    #[OA\Tag(name: "ArticleImage")]
    #[OA\Response(
        response: 200,
        description: "Zdjęcie ustawione jako główne",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "is_main", type: "boolean", example: true)
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono obrazka o podanym id"
    )]
    #[Route('/article/image/{id}/set-main', name: 'app_article_image_set_main', methods: ['PUT'])]
    public function setMain(
        ManagerRegistry $doctrine,
        ImageRepository $imageRepository,
        string $id
    ): JsonResponse {
        $entityManager = $doctrine->getManager();
        $image = $imageRepository->find((int)$id);

        if (!$image) {
            return new JsonResponse(['error' => 'Nie znaleziono obrazka o podanym id'], 404);
        }

        $articleId = $image->getIdArticle();

        // Unset all main images for this article
        $existingImages = $imageRepository->findByArticleOrdered($articleId);
        foreach ($existingImages as $existingImage) {
            if ($existingImage->isMain()) {
                $existingImage->setIsMain(false);
                $entityManager->persist($existingImage);
            }
        }

        // Set this image as main
        $image->setIsMain(true);
        $entityManager->persist($image);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $image->getId(),
            'is_main' => $image->isMain(),
        ]);
    }

    /**
     * Aktualizuje metadane zdjęcia (pozycja, bez zmiany pliku)
     */
    #[OA\Tag(name: "ArticleImage")]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "position", type: "integer", example: 2)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Zdjęcie zaktualizowane"
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono obrazka o podanym id"
    )]
    #[Route('/article/image/{id}', name: 'app_article_image_update', methods: ['PUT', 'PATCH'])]
    public function update(
        ManagerRegistry $doctrine,
        ImageRepository $imageRepository,
        string $id,
        Request $request
    ): JsonResponse {
        $entityManager = $doctrine->getManager();
        $image = $imageRepository->find((int)$id);

        if (!$image) {
            return new JsonResponse(['error' => 'Nie znaleziono obrazka o podanym id'], 404);
        }

        $requestArray = $request->toArray();

        if (isset($requestArray['position'])) {
            $image->setPosition((int)$requestArray['position']);
        }

        $entityManager->persist($image);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $image->getId(),
            'position' => $image->getPosition(),
        ]);
    }
}
