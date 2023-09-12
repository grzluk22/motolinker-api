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
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="id",
     *                         type="int",
     *                         description="Unikalne ID"
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
     *                      ),
     *                     example={
     *                      "id": 1,
     *                     "translations":{
     *                              {"id_criterion":1,
     *                                   {"pl":"Strona Mocowania"}
     *                                   }
     *     }
     *
     *                     }
     *                 )
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
    public function index(CriterionRepository $criterionRepository, CriterionLanguageRepository $criterionLanguageRepository): Response
    {
        $data = [];
        $criterions = $criterionRepository->findAll();
        foreach ($criterions as $criterion) {
            /* Pobieranie tłumaczeń dla tego kryteria */
            $translations = $criterionLanguageRepository->findBy(['id_criterion' => $criterion->getId()]);
            $data[] = ['id' => $criterion->getId(), 'translations' => $translations];
        }
        return $this->json($data);
    }

    /**
     * Dodaje nowe kryteria
     *
     * @OA\Tag(name="Criterion")
     * @OA\RequestBody(
     *     request="CriterionRequestBody",
     *     description="Kryterium",
     *     required=true,
     *     @OA\JsonContent(
     *        allOf={
     *                 @OA\Schema(
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
     *                         "translations": {"pl": "Nowe Kryterium"}
     *                     }
     *    )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Stworzone kryterium",
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
     *                         property="translations",
     *                         type="array",
     *                         description="Tablica tłumaczeń",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="pl",
     *                              type="string",
     *                              description="tłumaczenie_pl"
     *                          ))
     *                      ),
     *                     example={
     *                      "id": 1,
     *                     "translations":{
     *                                   {"pl":"Strona Mocowania"}
     *                                  }
     *
     *                     }
     *                 )
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
    public function add(ManagerRegistry $managerRegistry, CriterionRepository $criterionRepository, CriterionLanguageRepository $criterionLanguageRepository, LanguageRepository $languageRepository, Request $request) {
        /* Żadanie dodania nowego kryterium przyjmuje tylko tłumaczenia nazw dla danego kryterium */
        $requestArray = $request->toArray();
        $languageManager = $managerRegistry->getManagerForClass(Language::class);
        $criterionManager = $managerRegistry->getManagerForClass(Criterion::class);
        $criterionLanguageManager = $managerRegistry->getManagerForClass(CriterionLanguage::class);
        /* Sprawdzenie czy w dla głównego (o id 1) języka istnieje już kryterium o takiej nazwie */
        $defaultLanguage = $languageRepository->findOneById(1);
        if(isset($requestArray['translations'][$defaultLanguage->getIsoCode()])) {
            $searchCritertionLanguageRepository = $criterionLanguageRepository->findBy(["id_language"=>1, "name" => $requestArray['translations'][$defaultLanguage->getIsoCode()]]);
            if(count($searchCritertionLanguageRepository) > 0) {
                return $this->json(["error" => "Kryterium o takiej nazwie już istnieje"]);
            }
        }else{
            return $this->json(["error" => "Nie przekazano tłumaczenia dla głównego języka"]);
        }
        /*$translatedDefaultName = array_filter();*/

        /* Dodawanie tłumaczeń */
        if (!isset($requestArray['translations'])) {
            return $this->json(["error" => "Nie przekazano tłumaczeń"]);
        } else {
            if (count($requestArray['translations']) == 0) {
                return $this->json(["error" => "Nie przekazano tłumaczeń"]);
            } else {
                /* Sprawdzanie czy dany język istnieje w tabeli z językami jezeli nie to dodawanie go do tej tabli */
                foreach ($requestArray['translations'] as $languageName=>$translation) {
                    $langResult = $languageRepository->findOneBy(["isoCode" => $languageName]);
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
                $languageIds[$language->getIsoCode()] = $language->getId();
            }
            /* Tworzenie nowego criterion */
            $criterion = new Criterion();
            $criterionManager->persist($criterion);
            $criterionManager->flush();
            foreach ($requestArray['translations'] as $languageName=>$translation) {
                $criterionLanguage = new CriterionLanguage();
                $criterionLanguage->setName($translation);
                $criterionLanguage->setIdLanguage($languageIds[$languageName]);
                $criterionLanguage->setIdCriterion($criterion->getId());
                $criterionManager->persist($criterionLanguage);;
                $criterionManager->flush();
            }
        }
        return $this->json($requestArray);
    }

    /**
     * Edytuje kryterium
     *
     *
     *
     * @OA\Tag(name="Criterion")
     * @OA\RequestBody(
     *     request="CriterionEditRequestBody",
     *     description="Kryteria",
     *     required=true,
     *     @OA\JsonContent(
     *        allOf={
     *                 @OA\Schema(
     *                      @OA\Property(
     *                         property="id",
     *                         type="int",
     *                         description="ID Kryterium",
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
     *                         "translations": {"pl": "Nowe Kryterium"}
     *                     }
     *    )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Zaktualizowane kryterium",
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
     *                         property="translations",
     *                         type="array",
     *                         description="Tablica tłumaczeń",
     *                      @OA\Items(
     *                          @OA\Property(
     *                              property="pl",
     *                              type="string",
     *                              description="tłumaczenie_pl"
     *                          ))
     *                      ),
     *                     example={
     *                      "id": 1,
     *                     "translations":{
     *                              {"pl":"Strona Mocowania"}
     *     }
     *
     *                     }
     *                 )
     *             )
     *         })
     * )
     * * @OA\Response(
     *     response=404,
     *     description="Brak kryteriów"
     * )
     **/
    #[Route('/criterion', name: 'app_criterion_edit', methods: ["PUT"])]
    public function edit(ManagerRegistry $managerRegistry, CriterionRepository $criterionRepository, CriterionLanguageRepository $criterionLanguageRepository, LanguageRepository $languageRepository, Request $request)
    {
        $criterionManager = $managerRegistry->getManagerForClass(Criterion::class);
        $criterionLanguageManager = $managerRegistry->getManagerForClass(CriterionLanguage::class);
        $requestArray = $request->toArray();
        if(!isset($requestArray["id"])) return $this->json(["error" => "Nie przekazano id kryterium do edycji"]);
        if(!isset($requestArray["translations"])) return $this->json(["error" => "Nie przekazano tłumaczeń dla wybranego kryterium"]);
        $criterion = $criterionRepository->findOneBy(["id" => 1]);
        if($criterion === null) return $this->json(["error" => "Kryterium o podanym id nie istnieje"]);
        /* Pobieranie id wszystkich dostępnych języków */
        $languages = $languageRepository->findAll();
        $languageIds = [];
        foreach ($languages as $language) {
            $languageIds[$language->getIsoCode()] = $language->getId();
        }
        $criterionLanguages = $criterionLanguageRepository->findBy(["id_criterion" => $requestArray['id']]);
        /* Dla każdego przekazanego tłumaczenia dla danego kryterium zmieniamy i zapisujemy */
        foreach ($requestArray["translations"] as $languageCode=>$name) {
            /* Sprawdzanie czy język o takim isoCode istnieje w bazie danych */
            if(!isset($languageIds[$languageCode])) {
                return $this->json(["error" => "Język o podanym kodzie nie istnieje"]);
            }else{
                /* Sprawdzanie czy tłumaczenie dla tego kryterium w danym języku istnieje */
                    /* aktualizacja danego tłumaczenia */
                    $thisCriterionLanguage = $criterionLanguageRepository->findOneBy(['id_criterion' => $requestArray['id'], 'id_language' => $languageIds[$languageCode]]);
                    if($thisCriterionLanguage == null ) {
                        /*return $this->json(["error" => "Tłumaczenie dla tego kryterium dla tego języka (".$languageCode.") nie isnieje"]);*/
                        /* Zamiast tego wstawianie nowego języka */
                        $thisCriterionLanguage = new CriterionLanguage();
                        $thisCriterionLanguage->setIdCriterion($requestArray['id']);
                        $thisCriterionLanguage->setIdLanguage($languageIds[$languageCode]);
                    }
                    $thisCriterionLanguage->setName($name);
                    $criterionLanguageRepository->save($thisCriterionLanguage);
                    $criterionLanguageManager->persist($thisCriterionLanguage);
                    $criterionLanguageManager->flush();
            }
        }
        $updatedCriterion = $criterionLanguageRepository->findBy(['id_criterion' => $requestArray['id']]);
        return $this->json($updatedCriterion);
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
            return $this->json(["error" => "Nie znaleziono kryterium o podanym id"]);
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

        return $this->json("Usunięto");
    }
}
