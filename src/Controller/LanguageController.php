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
    public function index(LanguageRepository $languageRepository): Response
    {
        $languages = $languageRepository->findAll();
        return $this->json($languages);
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
            return $this->json(["error" => "Nie znaleziono artykułu o podanym kodzie"]);
        }
        /* Usuwanie języka */
        $entityManager->remove($language);
        $entityManager->flush();

        return $this->json("Usunięto");
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
     *     response=500,
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
            return $this->json(["error" => "Nie znaleziono języka o podanym id"]);
        }
        /* Sprawdzanie czy inny język nie ma już takiej nazwy */
        $otherLanguage = $languageRepository->findOneBy(['name' => $requestArray['name']]);
        if($otherLanguage !== null && $otherLanguage->getId() !== $requestArray['id']) {
            return $this->json(['error' => "Inny język już ma taką nazwę"]);
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

        return $this->json($data);
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
     *     response=500,
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
            return $this->json(['error' => "Inny język już ma taką nazwę"]);
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

        return $this->json($data);
    }
}
