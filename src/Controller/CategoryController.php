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
     *                     example={
     *                         "id": 1,
     *                         "id_parent": 1,
     *                         "translations": {
     *                              "id": 5,
     *                              "id_category": 3,
     *                              "id_language": 1,
     *                              "name": "Układ Hamulcowy 2",
     *                              "description": "Części układu hamulcowego"
     *                          }
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
    public function index(CategoryRepository $categoryRepository, CategoryLanguageRepository $categoryLanguageRepository): JsonResponse
    {
        $categories = $categoryRepository->findAll();
        if(!$categories) return new JsonResponse(['message' => 'Brak kategorii'], 404);
        $data = [];
        foreach ($categories as $id=>$category) {
            $data[] = [
                "id" => $category->getId(),
                "id_parent" => $category->getIdParent(),
                "translations" => $categoryLanguageRepository->findBy(['id_category' => $category->getId()])
            ];
        }
        return new JsonResponse($data);
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
     *                     example={
     *                         "id_parent": 1,
     *                         "translations": {
     *                              "id_language": 1,
     *                              "name": "Układ Hamulcowy 2",
     *                              "description": "Części układu hamulcowego"
     *                          }
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
     *                     example={
     *                         "id": 1,
     *                         "id_parent": 1,
     *                         "translations": {
     *                              "id": 5,
     *                              "id_category": 3,
     *                              "id_language": 1,
     *                              "name": "Układ Hamulcowy 2",
     *                              "description": "Części układu hamulcowego"
     *                          }
     *                     }
     *                 )
     *
     *         })
     * )
     * @OA\Response(
     *     response=400,
     *     description="Nie przekazano tłumaczeń"
     * )
     **/
    #[Route('/category', name: 'app_category_create', methods: ["POST"])]
    public function create(ManagerRegistry $managerRegistry, CategoryLanguageRepository $categoryLanguageRepository, Request $request): JsonResponse
    {
        $categoryManager = $managerRegistry->getManager();
        $requestArray = $request->toArray();
        $category = new Category();
        $category->setIdParent($requestArray['id_parent']);
        $categoryManager->persist($category);
        $categoryManager->flush();

        if(!isset($requestArray['translations']) or count($requestArray['translations']) == 0) return new JsonResponse(["message" => "Nie przekazano tłumaczeń"], 400);
        /* Dodawanie tłumaczeń */
        foreach ($requestArray['translations'] as $translation) {
            $categoryLanguage = new CategoryLanguage();
            $categoryLanguage->setName($translation['name']);
            $categoryLanguage->setIdLanguage($translation['id_language']);
            $categoryLanguage->setIdCategory($category->getId());
            $categoryLanguage->setDescription($translation['description']);
            $categoryManager->persist($categoryLanguage);;
            $categoryManager->flush();
        }
        $data = array_merge((array) $category, ["translations" => $categoryLanguageRepository->findBy(['id_category' => $category->getId()])]);

        return new JsonResponse($data);
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
            return new JsonResponse(["message" => "Nie znaleziono kategorii o podanym id"], 404);
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

        return new JsonResponse(['message' => "Usunięto"]);
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
     *                     example={
     *                         "id": 1,
     *                         "id_parent": 1,
     *                         "translations": {
     *                              "id": 5,
     *                              "id_category": 3,
     *                              "id_language": 1,
     *                              "name": "Układ Hamulcowy 2",
     *                              "description": "Części układu hamulcowego"
     *                          }
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
     *                     example={
     *                         "id": 1,
     *                         "id_parent": 1,
     *                         "translations": {
     *                              "id": 5,
     *                              "id_category": 3,
     *                              "id_language": 1,
     *                              "name": "Układ Hamulcowy 2",
     *                              "description": "Części układu hamulcowego"
     *                          }
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
    public function edit(ManagerRegistry $managerRegistry, CategoryRepository $categoryRepository, CategoryLanguageRepository $categoryLanguageRepository, Request $request): JsonResponse
    {
        $categoryManager = $managerRegistry->getManager();
        $requestArray = $request->toArray();
        $category = $categoryRepository->findOneBy(['id' => $requestArray['id']]);
        if(!$category) return new JsonResponse(['message' => 'Nie znaleziono kategorii o podanym id'], 404);
        $category->setIdParent($requestArray['id_parent']);
        $categoryManager->persist($category);
        $categoryManager->flush();

        /* Ustawianie tłumaczeń */
        foreach ($requestArray['translations'] as $translation) {
            if(!isset($translation['id'])) {
                $categoryLanguage = new CategoryLanguage();
                $categoryLanguage->setIdLanguage($translation['id_language']);
                $categoryLanguage->setIdCategory($category->getId());
            }else{
                $categoryLanguage = $categoryLanguageRepository->findOneBy(['id' => $translation['id']]);
            }
            $categoryLanguage->setName($translation['name']);
            $categoryLanguage->setDescription($translation['description']);
            $categoryLanguageRepository->save($categoryLanguage, true);
        }

        $data = array_merge((array) $category, ["translations" => $categoryLanguageRepository->findBy(['id_category' => $category->getId()])]);

        return new JsonResponse($data);
    }
}
