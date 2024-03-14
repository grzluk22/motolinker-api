<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\CategoryLanguage;
use App\Entity\Image;
use App\Entity\Language;
use App\Repository\CategoryLanguageRepository;
use App\Repository\CategoryRepository;
use App\Repository\ImageRepository;
use App\Repository\LanguageRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class ImageController extends AbstractController
{
    /**
     * Wyświetla liste obrazków dla danego produktu
     *
     * @OA\Tag(name="ArticleImage")
     *
     * @OA\Response(
     *     response=200,
     *     description="Lista obrazków dla danego produktu",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                         "id_article": 1,
     *                         "position": 1,
     *                         "url": "http://motolinker-media.localhost/uploads/0f4fb171-6a05-4a33-9be5-571cc07a59c5.png",
     *                     }
     *                 )
     *
     *         })
     * )
     * * @OA\Response(
     *     response=404,
     *     description="Brak Obrazków"
     * )
     *
     *
     * */
    #[Route('/article/{id_article}/image', name: 'app_article_image_get', methods:['GET'])]
    public function index(ImageRepository $imageRepository, string $id_article): JsonResponse
    {
        $images = $imageRepository->findBy(['id_article' => $id_article]);
        if(!$images) {
            return new JsonResponse(["error" => "Nie znaleziono obrazków dla danego id artykułu"]);
        }
        return new JsonResponse($images);
    }

    /**
     * Wstawia obrazek dla danego artykułu
     *
     *
     *
     * @OA\Tag(name="ArticleImage")
     * @OA\RequestBody(
     *     request="ArticleImageCreateRequestBody",
     *     description="ArticleID oraz ImageURL",
     *     required=true,
     *     @OA\JsonContent(
     *                     example={
     *                         "id_article": 1,
     *                         "position": 1,
     *                         "url": "http://motolinker-media.localhost/uploads/0f4fb171-6a05-4a33-9be5-571cc07a59c5.png"
     *                     }
     *    )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Dodany obrazek artykułu",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                         "id_article": 1,
     *                         "position": 1,
     *                         "url": "http://motolinker-media.localhost/uploads/0f4fb171-6a05-4a33-9be5-571cc07a59c5.png"
     *                     }
     *                 )
     *
     *         })
     * )
     * @OA\Response(
     *     response=400,
     *     description="Nie podano url i/lub id_article"
     * )
     **/
    #[Route('/article/image', name: 'app_article_image_post', methods: ["POST"])]
    public function post(ManagerRegistry $doctrine, ImageRepository $imageRepository, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $requestArray = $request->toArray();
        $image =  new Image();
        $image->setIdArticle($requestArray['id_article']);
        $image->setPosition($requestArray['position']);
        $image->setUrl($requestArray['url']);
        $entityManager->persist($image);
        $entityManager->flush();
        return new JsonResponse($image);
    }

    /**
     * Usuwa Obrazek z produktu
     *
     * @OA\Tag(name="ArticleImage")
     * @OA\Response(
     *     response=200,
     *     description="Usunięto"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono obrazka o podanym id"
     * )
     **/
    #[Route('/article/image/{id}', name: 'app_article_image_delete', methods: ["DELETE"])]
    public function delete(ManagerRegistry $doctrine, ImageRepository $imageRepository, string $id): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $image = $imageRepository->findOneBy(['id' => $id]);
        if(!$image) return new JsonResponse(['message' => 'Nie znaleziono obrazka o podanym id']);
        $entityManager->remove($image);;
        $entityManager->flush();

        return new JsonResponse(['message' => "Usunięto"]);
    }
}
