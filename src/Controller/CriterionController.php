<?php

namespace App\Controller;

use App\Entity\CategoryLanguage;
use App\Entity\Criterion;
use App\Entity\CriterionLanguage;
use App\Entity\Language;
use App\Repository\ArticleCriterionRepository;
use App\Repository\CriterionLanguageRepository;
use App\Repository\CriterionRepository;
use App\Repository\CriterionValueLanguageRepository;
use App\Repository\LanguageRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
