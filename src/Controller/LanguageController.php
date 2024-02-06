<?php

namespace App\Controller;

use App\Entity\ArticleLanguage;
use App\Entity\Language;
use App\Repository\ArticleLanguageRepository;
use App\Repository\ArticleRepository;
use App\Repository\LanguageRepository;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class LanguageController extends AbstractController
{
    /**
     * Wyświetla liste języków
     *
     * @OA\Tag(name="Language")
     * @OA\Response(
     *     response=200,
     *     description="Lista języków",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                         "id": 1,
     *                         "name": "Polski",
     *                         "isoCode": "pl-PL"
     *                     }
     *             )
     *         })
     * )
     * * @OA\Response(
     *     response=404,
     *     description="Brak języków"
     * )
     *
     */
    #[Route('/language', name: 'app_language_get', methods: ["GET"])]
    public function index(LanguageRepository $languageRepository): JsonResponse
    {
        $languages = $languageRepository->findAll();
        if(!$languages) return new JsonResponse(['message' => 'Brak języków'], 404);
        return new JsonResponse($languages);
    }

    /**
     * Usuwa język
     *
     * @OA\Tag(name="Language")
     * @OA\Response(
     *     response=200,
     *     description="Usunięto"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono języka o podanym id"
     * )
     **/
    #[Route('/language/{id}', name: 'app_language_delete', methods: ["DELETE"])]
    public function delete(ManagerRegistry $doctrine, LanguageRepository $languageRepository, string $id): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $language = $languageRepository->findOneBy(["id" => $id]);
        if($language === null) {
            return new JsonResponse(["message" => "Nie znaleziono artykułu o podanym kodzie"], 404);
        }
        /* Usuwanie języka */
        $entityManager->remove($language);
        $entityManager->flush();

        return new JsonResponse(['message' => "Usunięto"]);
    }

    /**
     * Edytuje język
     *
     *
     *
     * @OA\Tag(name="Language")
     * @OA\RequestBody(
     *     request="LanguageEditRequestBody",
     *     description="Artykuł",
     *     required=true,
     *     @OA\JsonContent(
     *                     example={
     *                         "id": "1",
     *                         "name": "Polski",
     *                         "isoCode": "pl-PL"
     *                     }
     *    )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Zaktualizowany język",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                         "id": "1",
     *                         "name": "Polski",
     *                         "isoCode": "pl-PL"
     *                     }
     *                 )
     *
     *         })
     * )
     * @OA\Response(
     *     response=400,
     *     description="Inny język już ma taką nazwę"
     * )
     **/
    #[Route('/language', name: 'app_language_edit', methods: ["PUT"])]
    public function edit(ManagerRegistry $doctrine, LanguageRepository $languageRepository, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $requestArray = $request->toArray();
        $language = $languageRepository->findOneBy(['id' => $requestArray['id']]);
        if($language === null) {
            return new JsonResponse(["message" => "Nie znaleziono języka o podanym id"], 404);
        }
        /* Sprawdzanie czy inny język nie ma już takiej nazwy */
        $otherLanguage = $languageRepository->findOneBy(['name' => $requestArray['name']]);
        if($otherLanguage !== null && $otherLanguage->getId() !== $requestArray['id']) {
            return new JsonResponse(['message' => "Inny język już ma taką nazwę"], 400);
        }
        /* Ustawianie danych języka */
        $language->setName($requestArray['name']);
        $language->setIsoCode($requestArray['isoCode']);
        $entityManager->persist($language);
        $entityManager->flush();

        $data = [
            'id' => $language->getId(),
            'name' => $language->getName(),
            'isoCode' => $language->getIsoCode()
        ];

        return new JsonResponse($data);
    }

    /**
     * Tworzy język
     *
     *
     *
     * @OA\Tag(name="Language")
     * @OA\RequestBody(
     *     request="LanguageCreateRequestBody",
     *     description="Język",
     *     required=true,
     *     @OA\JsonContent(
     *                     example={
     *                         "name": "Polski",
     *                         "isoCode": "pl-PL"
     *                     }
     *    )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Dodany język",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                         "id": "1",
     *                         "name": "Polski",
     *                         "isoCode": "pl-PL"
     *                     }
     *                 )
     *
     *         })
     * )
     * @OA\Response(
     *     response=400,
     *     description="Inny język już ma taką nazwę"
     * )
     **/
    #[Route('/language', name: 'app_language_create', methods: ["POST"])]
    public function create(ManagerRegistry $doctrine, LanguageRepository $languageRepository, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $requestArray = $request->toArray();
        /* Sprawdzanie czy inny język nie ma już takiej nazwy */
        $otherLanguage = $languageRepository->findOneBy(['name' => $requestArray['name']]);
        if($otherLanguage !== null) {
            return new JsonResponse(['message' => "Inny język już ma taką nazwę"], 400);
        }
        /* Ustawianie danych języka */
        $language = new Language();
        $language->setName($requestArray['name']);
        $language->setIsoCode($requestArray['isoCode']);
        $entityManager->persist($language);
        $entityManager->flush();

        $data = [
            'id' => $language->getId(),
            'name' => $language->getName(),
            'isoCode' => $language->getIsoCode()
        ];

        return new JsonResponse($data);
    }
}
