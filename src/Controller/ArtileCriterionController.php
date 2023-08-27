<?php

namespace App\Controller;

use App\Repository\ArticleCriterionRepository;
use App\Repository\CriterionLanguageRepository;
use App\Repository\CriterionValueLanguageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class ArtileCriterionController extends AbstractController
{
    /**
     * Lista kryteriów wraz z tłumaczeniami dla danego artykułu
     *
     * @OA\Tag(name="Article")
     * @OA\Response(
     *     response=200,
     *     description="Lista kryteriów artykułu o podanym id",
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
     *                         property="id_criterion",
     *                         type="int",
     *                         description="ID Kryterium"
     *                     ),
     *                     @OA\Property(
     *                         property="id_article",
     *                         type="int",
     *                         description="ID Artykułu"
     *                     ),
     *                     @OA\Property(
     *                         property="value",
     *                         type="string",
     *                         description="Wartość danego kryteria"
     *                     ),
     *                     @OA\Property(
     *                         property="value_description",
     *                         type="string",
     *                         description="Dluższy opis/wartość kryterium"
     *                     ),
     *
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
     *                     example={{
     *                         "id": 1,
     *                         "id_article": "1",
     *                         "id_criterion": "12",
     *                         "value": "Oś tylna",
     *                         "value_description": "Oś tylna po obydwu stronach",
     *                         "translations": {
     *                              "pl": {
     *                                  "value":"tlumaczenie",
     *                                  "value_description": "tlumaczenie description"
     *                                  }
     *                          }
     *                      },
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
    #[Route('/article/{id_article}/criterion', name: 'app_article_criterion', methods: ['GET'])]
    public function index(ArticleCriterionRepository $articleCriterionRepository, CriterionLanguageRepository $criterionLanguageRepository, CriterionValueLanguageRepository $criterionValueLanguageRepository, int $id_article): Response
    {
        $criterions = $articleCriterionRepository->findBy(['id_article' => $id_article]);
        if(count($criterions) == 0) return $this->json(['message' => 'Nie znaleziono kryteriów dla artykułu o podanym id'], 404);
        $criterionTranslations = $criterionLanguageRepository->findAll();
        foreach ($criterions as $index=>$criterion) {
            $criterions[$index]->translations = $criterionValueLanguageRepository->findBy(['id_article_criterion' => $criterion->getId()]);
        }
        $data = ["criterions" => $criterions, "translations" => $criterionTranslations];
        return $this->json($data);
    }
}
