<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\CategoryLanguage;
use App\Entity\Language;
use App\Repository\CategoryLanguageRepository;
use App\Repository\CategoryRepository;
use App\Repository\LanguageRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class CategoryController extends AbstractController
{
    /**
     * Wyświetla liste kategorii
     *
     * @OA\Tag(name="Category")
     *
     * @OA\Response(
     *     response=200,
     *     description="Lista kategorii",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         description="Unikalne ID"
     *                     ),
     *                     @OA\Property(
     *                         property="id_parent",
     *                         type="string",
     *                         description="ID kategorii rodzica",
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
     *                 ),
     *                     example={
     *                         "id": 1,
     *                         "id_parent": 1,
     *                         "translations": {"Polski": "Zawieszenie"}
     *                     }
     *                 )
     *
     *         })
     * )
     * * @OA\Response(
     *     response=404,
     *     description="Brak kategorii"
     * )
     *
     *
     * */
    #[Route('/category', name: 'app_category_get', methods:['GET'])]
    public function index(CategoryRepository $categoryRepository, CategoryLanguageRepository $categoryLanguageRepository): Response
    {
        $categories = $categoryRepository->findAll();
        $data = [];
        foreach ($categories as $id=>$category) {
            $data[] = [
                "id" => $category->getId(),
                "id_parent" => $category->getIdParent(),
                "translations" => $categoryLanguageRepository->findBy(['id_category' => $category->getId()])
            ];
        }
        return $this->json($data);
    }

    /**
     * Tworzy kategorie
     *
     *
     *
     * @OA\Tag(name="Category")
     * @OA\RequestBody(
     *     request="CategoryCreateRequestBody",
     *     description="Kategoria",
     *     required=true,
     *     @OA\JsonContent(
     *        allOf={
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="id_parent",
     *                         type="string",
     *                         description="ID kategorii rodzica",
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
     *                 )
     *        },
     *                     example={
     *                         "id_parent": 1,
     *                         "translations": {"Polski": "Zawieszenie"}
     *                     }
     *    )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Dodana kategoria",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         description="Unikalne ID"
     *                     ),
     *                     @OA\Property(
     *                         property="id_parent",
     *                         type="string",
     *                         description="ID kategorii rodzica",
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
     *                 ),
     *                     example={
     *                         "id": 1,
     *                         "id_parent": 1,
     *                         "translations": {"Polski": "Zawieszenie"}
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
    #[Route('/category', name: 'app_category_create', methods: ["POST"])]
    public function create(ManagerRegistry $managerRegistry, LanguageRepository $languageRepository, CategoryRepository $categoryRepository, CategoryLanguageRepository $categoryLanguageRepository, Request $request): JsonResponse
    {
        $categoryManager = $managerRegistry->getManager();
        $languageManager = $managerRegistry->getManagerForClass(Language::class);
        $requestArray = $request->toArray();
        $category = new Category();
        $category->setIdParent($requestArray['id_parent']);
        $categoryManager->persist($category);
        $categoryManager->flush();

        /* Dodawanie tłumaczeń */
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
            /* Pobieranie id wszystkich dostępnych języków */
            $languages = $languageRepository->findAll();
            $languageIds = [];
            foreach ($languages as $language) {
                $languageIds[$language->getName()] = $language->getId();
            }
            foreach ($requestArray['translations'] as $languageName=>$translation) {
                $categoryLanguage = new CategoryLanguage();
                $categoryLanguage->setName($translation);
                $categoryLanguage->setIdLanguage($languageIds[$languageName]);
                $categoryLanguage->setIdCategory($category->getId());
                $categoryLanguage->setDescription('asd');
                $categoryManager->persist($categoryLanguage);;
                $categoryManager->flush();
            }
        }

        $data = [
            'id' => $category->getId(),
            'id_parent' => $category->getIdParent(),
            'translations' => $categoryLanguageRepository->findBy(['id_category' => $category->getId()])
        ];

        return $this->json($data);
    }

    /**
     * Usuwa kategorie
     *
     * @OA\Tag(name="Category")
     * @OA\Response(
     *     response=200,
     *     description="Usunięto"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono kategorii o podanym id"
     * )
     **/
    #[Route('/category/{id}', name: 'app_category_delete', methods: ["DELETE"])]
    public function delete(ManagerRegistry $doctrine, CategoryRepository $categoryRepository, CategoryLanguageRepository $categoryLanguageRepository, string $id): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $categoryLanguageManager = $doctrine->getManagerForClass(CategoryLanguage::class);
        $category = $categoryRepository->findOneBy(["id" => $id]);
        if($category === null) {
            return $this->json(["error" => "Nie znaleziono kategorii o podanym id"]);
        }
        /* Usuwanie kategorii */
        $entityManager->remove($category);
        $entityManager->flush();

        /* Usuwanie tłumaczeń dla tej kategorii */
        $categoryLanguages = $categoryLanguageRepository->findBy(['id_category' => $id]);
        foreach ($categoryLanguages as $categoryLanguage) {
            $categoryLanguageManager->remove($categoryLanguage);
            $categoryLanguageManager->flush();
        }

        return $this->json("Usunięto");
    }

    /**
     * Edytuje kategorie
     *
     *
     *
     * @OA\Tag(name="Category")
     * @OA\RequestBody(
     *     request="CategoryEditRequestBody",
     *     description="Kategoria",
     *     required=true,
     *     @OA\JsonContent(
     *        allOf={
     *                 @OA\Schema(
     *                      @OA\Property(
     *                         property="id",
     *                         type="int",
     *                         description="ID Kategorii",
     *                     ),
     *                     @OA\Property(
     *                         property="id_parent",
     *                         type="int",
     *                         description="ID kategorii rodzica",
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
     *                 )
     *        },
     *                     example={
     *                         "id": 1,
     *                         "id_parent": 0,
     *                         "translations": {"Polski": "Zawieszenie"}
     *                     }
     *    )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Zaktualizowana kategoria",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         description="Unikalne ID"
     *                     ),
     *                     @OA\Property(
     *                         property="id_parent",
     *                         type="string",
     *                         description="ID kategorii rodzica",
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
     *                 ),
     *                     example={
     *                         "id": 1,
     *                         "id_parent": 1,
     *                         "translations": {"Polski": "Zawieszenie"}
     *                     }
     *                 )
     *
     *         })
     * )
     * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono kategorii o podanym id"
     * )
     **/
    #[Route('/category', name: 'app_category_edit', methods: ["PUT"])]
    public function edit(ManagerRegistry $managerRegistry, LanguageRepository $languageRepository, CategoryRepository $categoryRepository, CategoryLanguageRepository $categoryLanguageRepository, Request $request): JsonResponse
    {
        $categoryManager = $managerRegistry->getManager();
        $languageManager = $managerRegistry->getManagerForClass(Language::class);
        $requestArray = $request->toArray();
        $category = $categoryRepository->findOneBy(['id' => $requestArray['id']]);
        $category->setIdParent($requestArray['id_parent']);
        $categoryManager->persist($category);
        $categoryManager->flush();

        /* Ustawianie tłumaczeń */
        /* Sprawdzanie czy dany język istnieje w tabeli z językami jezeli nie to dodawanie go do tej tabli */
        foreach ($requestArray['translations'] as $languageName=>$translation) {
            $langResult = $languageRepository->findOneByName($languageName);
            if ($langResult === null) {
                /* Wstawianie nowego języka */
                $language = new Language();
                $language->setName($languageName);
                $language->setIsoCode($languageName);
                $languageManager->persist($language);
                $languageManager->flush();
            }
        }
        /* Pobieranie id wszystkich dostępnych języków */
        $languages = $languageRepository->findAll();
        $languageIds = [];
        foreach ($languages as $language) {
            $languageIds[$language->getName()] = $language->getId();
        }
        foreach ($requestArray['translations'] as $languageName=>$translation) {
            $categoryLanguage = $categoryLanguageRepository->findOneBy(['id_category' => $category->getId(), 'id_language' => $languageIds[$languageName]]);
            if($categoryLanguage === null) {
                $categoryLanguage = new CategoryLanguage();
                $categoryLanguage->setIdCategory($category->getId());
                $categoryLanguage->setIdLanguage($languageIds[$languageName]);
            }
            $categoryLanguage->setName($translation);
            $categoryLanguage->setDescription('asd');
            $categoryManager->persist($categoryLanguage);;
            $categoryManager->flush();
        }

        $data = [
            'id' => $category->getId(),
            'id_parent' => $category->getIdParent(),
            'translations' => $categoryLanguageRepository->findBy(['id_category' => $category->getId()])
        ];

        return $this->json($data);
    }
}
