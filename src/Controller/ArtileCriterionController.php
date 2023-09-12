<?php

namespace App\Controller;

use App\Entity\ArticleCriterion;
use App\Entity\CriterionLanguage;
use App\Repository\ArticleCriterionRepository;
use App\Repository\ArticleRepository;
use App\Repository\CriterionLanguageRepository;
use App\Repository\CriterionRepository;
use App\Repository\CriterionValueLanguageRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
    public function index(ArticleRepository $articleRepository, ArticleCriterionRepository $articleCriterionRepository, CriterionLanguageRepository $criterionLanguageRepository, CriterionValueLanguageRepository $criterionValueLanguageRepository, int $id_article): Response
    {
        $criterions = $articleCriterionRepository->findBy(['id_article' => $id_article]);
        if(count($criterions) == 0) return $this->json(['message' => 'Nie znaleziono kryteriów dla artykułu o podanym id'], 404);
        $criterionTranslations = $criterionLanguageRepository->findAll();
        foreach ($criterions as $index=>$criterion) {
            $criterions[$index]->translations = $criterionValueLanguageRepository->findBy(['id_article_criterion' => $criterion->getId()]);
        }
        $data = ["criterions" => $criterions];
        return $this->json($data);
    }

    /**
     * Dodaje nowe kryterium do artykulu
     *
     * @OA\Tag(name="Article")
     *
     * @OA\RequestBody(
     *     request="ArticleCriterionAddRequestBody",
     *     description="Kategoria",
     *     required=true,
     *     @OA\JsonContent(
     *        allOf={
     *                 @OA\Schema(
     *                      @OA\Property(
     *                         property="id_article",
     *                         type="int",
     *                         description="ID Artykułu",
     *                     ),
     *                     @OA\Property(
     *                         property="id_criterion",
     *                         type="int",
     *                         description="ID Kryterium",
     *                     ),
     *                     @OA\Property(
     *                         property="value",
     *                         type="string",
     *                         description="Wartość (np: P)",
     *                     ),
     *                     @OA\Property(
     *                         property="value_description",
     *                         type="string",
     *                         description="Opis wartości (np: Przód)",
     *                     ),
     *                 )
     *        },
     *                     example={
     *                         "id_article": 6,
     *                         "id_criterion": 1,
     *                         "value": "P",
     *                         "value_description": "Przód"
     *                     }
     *    )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Dodane kryterium do artykułu",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                      @OA\Property(
     *                         property="id_article",
     *                         type="int",
     *                         description="ID Artykułu",
     *                     ),
     *                     @OA\Property(
     *                         property="id_criterion",
     *                         type="int",
     *                         description="ID Kryterium",
     *                     ),
     *                     @OA\Property(
     *                         property="value",
     *                         type="string",
     *                         description="Wartość (np: P)",
     *                     ),
     *                     @OA\Property(
     *                         property="value_description",
     *                         type="string",
     *                         description="Opis wartości (np: Przód)",
     *                     ),
     *                 ),
     *                     example={
     *                         "id_article": 6,
     *                         "id_criterion": 1,
     *                         "value": "P",
     *                         "value_description": "Przód"
     *                     }
     *                 )
     *
     *         })
     * )
     * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono artykułu/kryterium o podanym id"
     * )
     */
    #[Route('/article/{id_article}/criterion', name: 'app_article_criterion_add', methods: ['POST'])]
    public function add(ArticleCriterionRepository $articleCriterionRepository, ArticleRepository $articleRepository, CriterionRepository $criterionRepository, Request $request)
    {
        $requestArray = $request->toArray();
        $article = $articleRepository->findOneBy(['id' => $requestArray['id_article']]);
        if ($article == null) return $this->json(['error' => 'Nie znaleziono artykulu o podanym id']);
        $criterion = $criterionRepository->findOneBy(['id' => $requestArray['id_criterion']]);
        if($criterion == null) return $this->json(['error' => 'Nie znaleziono kryterium o podanym id']);
        $thisArticleCriterion = $articleCriterionRepository->findOneBy(['id_article' => $requestArray['id_article'], 'id_criterion' => $requestArray['id_criterion']]);
        if($thisArticleCriterion !== null) return $this->json(['error' => 'Dla tego artykułu już istnieje kryterium o podanym id']);
        $articleCriterion = new ArticleCriterion();
        $articleCriterion->setIdCriterion($requestArray['id_criterion']);
        $articleCriterion->setIdArticle($requestArray['id_article']);
        $articleCriterion->setValue($requestArray['value']);
        $articleCriterion->setValueDescription($requestArray['value_description']);
        $articleCriterionRepository->save($articleCriterion,true);
        return $this->json($articleCriterion);
    }

    /**
     * Usuwa kryterium z artykułu
     *
     * @OA\Tag(name="Article")
     * @OA\Response(
     *     response=200,
     *     description="Usunięto"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono kryterium/artykułu o podanym id"
     * )
     **/
    #[Route('/article/{id_article}/criterion/{id_criterion}', name: 'app_article_criterion_delete', methods: ["DELETE"])]
    public function delete(ArticleCriterionRepository $articleCriterionRepository, ArticleRepository $articleRepository, CriterionRepository $criterionRepository, int $id_article, int $id_criterion): JsonResponse
    {
        $criterion = $articleCriterionRepository->findOneBy(['id_article' => $id_article, 'id_criterion' => $id_criterion]);
        if($criterion == null) return $this->json(['error' => 'Nie istnieje takie kryterium']);
        $articleCriterionRepository->remove($criterion, true);
        return $this->json("Usunięto");
    }

}
