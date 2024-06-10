<?php

namespace App\Controller;

use App\Entity\CategoryLanguage;
use App\Entity\Criterion;
use App\Entity\CriterionLanguage;
use App\Entity\Language;
use App\Repository\ArticleCriterionRepository;
use App\Repository\CategoryLanguageRepository;
use App\Repository\CategoryRepository;
use App\Repository\CriterionLanguageRepository;
use App\Repository\CriterionRepository;
use App\Repository\CriterionValueLanguageRepository;
use App\Repository\LanguageRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use function Symfony\Component\Cache\Traits\object;

class CriterionController extends AbstractController
{
    /**
     * Lista wszystkich kryteriów wraz z tłumaczeniami
     *
     * @OA\Tag(name="Criterion")
     * @OA\Response(
     *     response=200,
     *     description="Lista kryteriów",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                      "id": 1,
     *                     "translations":{
     *                          "id": 3,
     *                          "id_criterion": 1,
     *                          "id_language": 6,
     *                          "name": "Strona mocowania"
     *                      }
     *
     *                     }
     *             )
     *         })
     * )
     * * @OA\Response(
     *     response=404,
     *     description="Brak kryteriów"
     * )
     *
     */
    #[Route('/criterion', name: 'app_criterion_get', methods: ['GET'])]
    public function index(CriterionRepository $criterionRepository, CriterionLanguageRepository $criterionLanguageRepository): JsonResponse
    {
        $data = [];
        $criterions = $criterionRepository->findAll();
        if(!$criterions) return new JsonResponse(['message' => 'Brak kryteriów w bazie danych'], 404);
        foreach ($criterions as $criterion) {
            /* Pobieranie tłumaczeń dla tego kryteria */
            $translations = $criterionLanguageRepository->findBy(['id_criterion' => $criterion->getId()]);
            $data[] = ['id' => $criterion->getId(), 'translations' => $translations];
        }
        return new JsonResponse($data);
    }

    /**
     * Tworzy nowe kryterium
     *
     * @OA\Tag(name="Criterion")
     * @OA\RequestBody(
     *     request="CriterionAddRequestBody",
     *     description="Kryterium",
     *     required=true,
     *     @OA\JsonContent(
     *                     example={
     *                     "translations":{
     *                          "id_language": 6,
     *                          "name": "Strona mocowania"
     *                      }
     *
     *                     }
     *    )
     * )

     * @OA\Response(
     *     response=200,
     *     description="Lista kryteriów",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                      "id": 1,
     *                     "translations":{
     *                          "id": 3,
     *                          "id_criterion": 1,
     *                          "id_language": 6,
     *                          "name": "Strona mocowania"
     *                      }
     *
     *                     }
     *             )
     *         })
     * )
     * * @OA\Response(
     *     response=404,
     *     description="Brak kryteriów"
     * )
     *
     */
    #[Route('/criterion', name: 'app_criterion_add', methods: ['POST'])]
    public function add(CriterionRepository $criterionRepository, CriterionLanguageRepository $criterionLanguageRepository, Request $request) {
        /* Żadanie dodania nowego kryterium przyjmuje tylko tłumaczenia nazw dla danego kryterium */
        $requestArray = $request->toArray();
        $criterion = new Criterion();
        $criterionRepository->save($criterion, true);
        foreach ($requestArray['translations'] as $translation) {
            $criterionLanguage = new CriterionLanguage();
            $criterionLanguage->setIdCriterion($criterion->getId());
            $criterionLanguage->setIdLanguage($translation['id_language']);
            $criterionLanguage->setName($translation['name']);
            $criterionLanguageRepository->save($criterionLanguage, true);
        }
        $data = (object) array_merge((array) $criterion, ["translations" => $criterionLanguageRepository->findBy(['id_criterion' => $criterion->getId()])]);
        return new JsonResponse($data);
    }

    /**
     * Edytuje kryterium
     *
     * @OA\Tag(name="Criterion")
     * @OA\RequestBody(
     *     request="CriterionAddRequestBody",
     *     description="Kryterium",
     *     required=true,
     *     @OA\JsonContent(
     *                     example={
     *                      "id": 1,
     *                     "translations":{
     *                          "id": 3,
     *                          "id_criterion": 1,
     *                          "id_language": 6,
     *                          "name": "Strona mocowania"
     *                      }
     *
     *                     }
     *    )
     * )

     * @OA\Response(
     *     response=200,
     *     description="Zaktualizowane kryterium",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                      "id": 1,
     *                     "translations":{
     *                          "id": 3,
     *                          "id_criterion": 1,
     *                          "id_language": 6,
     *                          "name": "Strona mocowania"
     *                      }
     *
     *                     }
     *             )
     *         })
     * )
     * * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono kryterium o podanym id"
     * )
     *
     */
    #[Route('/criterion', name: 'app_criterion_edit', methods: ["PUT"])]
    public function edit(CriterionRepository $criterionRepository, CriterionLanguageRepository $criterionLanguageRepository, Request $request)
    {
        $requestArray = $request->toArray();
        $criterion = $criterionRepository->findOneBy(['id' => $requestArray['id']]);
        if($criterion == null) return new JsonResponse(['message' => 'Nie znaleziono kryterium o podanym id'], 404);
        foreach ($requestArray['translations'] as $translation) {
            if(!isset($translation['id'])) {
                $criterionLanguage = new CriterionLanguage();
                $criterionLanguage->setIdLanguage($translation['id_language']);
                $criterionLanguage->setIdCriterion($criterion->getId());
            }else{
                $criterionLanguage = $criterionLanguageRepository->findOneBy(['id' => $translation['id']]);
            }
            $criterionLanguage->setName($translation['name']);
            $criterionLanguageRepository->save($criterionLanguage, true);
        }
        $data = (object) array_merge((array) $criterion, ["translations" => $criterionLanguageRepository->findBy(['id_criterion' => $criterion->getId()])]);
        return new JsonResponse($data);
    }


    /**
     * Usuwa kryterium
     *
     * @OA\Tag(name="Criterion")
     * @OA\Response(
     *     response=200,
     *     description="Usunięto"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono kryterium o podanym id"
     * )
     **/
    #[Route('/criterion/{id}', name: 'app_criterion_delete', methods: ["DELETE"])]
    public function delete(ManagerRegistry $doctrine, CriterionRepository $criterionRepository, CriterionLanguageRepository $criterionLanguageRepository, string $id): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $criterionLanguageManager = $doctrine->getManagerForClass(CriterionLanguage::class);
        $criterion = $criterionRepository->findOneBy(["id" => $id]);
        if($criterion === null) {
            return new JsonResponse(["message" => "Nie znaleziono kryterium o podanym id"], 404);
        }
        /* Usuwanie kategorii */
        $entityManager->remove($criterion);
        $entityManager->flush();

        /* Usuwanie tłumaczeń dla tego kryterium */
        $criterionLanguages = $criterionLanguageRepository->findBy(['id_criterion' => $id]);
        foreach ($criterionLanguages as $criterionLanguage) {
            $criterionLanguageManager->remove($criterionLanguage);
            $criterionLanguageManager->flush();
        }

        return new JsonResponse(['message' => "Usunięto"]);
    }

    /**
     * Usuwa pojedyńcze tłumaczenie kryterium
     *
     * @OA\Tag(name="Criterion")
     * @OA\Response(
     *     response=200,
     *     description="Usunięto"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono tłumaczenia kryterium o podanym id"
     * )
     **/
    #[Route('/criterion/translation/{trid}', name: 'app_criterion_translation_delete', methods: ["DELETE"])]
    public function deleteTranslation(ManagerRegistry $doctrine, CriterionRepository $criterionRepository, CriterionLanguageRepository $criterionLanguageRepository, string $trid): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $criterionLanguageManager = $doctrine->getManagerForClass(CriterionLanguage::class);

        /* Usuwanie tłumaczeń dla tego kryterium */
        $criterionLanguage = $criterionLanguageRepository->findOneBy(['id' => $trid]);
        $criterionLanguageManager->remove($criterionLanguage);
        $criterionLanguageManager->flush();

        return new JsonResponse(['message' => "Usunięto"]);
    }
}
