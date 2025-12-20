<?php

namespace App\Controller;

use App\Entity\ArticleCriterion;
use App\Entity\ArticleCriterionValueDescriptionLanguage;
use App\Entity\CriterionLanguage;
use App\Repository\ArticleCriterionRepository;
use App\Repository\ArticleCriterionValueDescriptionLanguageRepository;
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
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use App\HttpRequestModel\ArticleCriterionCreateRequest;
use App\HttpRequestModel\ArticleCriterionUpdateRequest;
use App\HttpResponseModel\ArticleCriterionResponse;
use App\HttpResponseModel\MessageResponse;

class ArtileCriterionController extends AbstractController
{
    /**
     * Lista kryteriów wraz z tłumaczeniami dla danego artykułu
     */
    #[OA\Tag(name: "Article")]
    #[OA\Response(
        response: 200,
        description: "Lista kryteriow",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ArticleCriterionResponse::class))
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Brak kryteriów"
    )]
    #[Route('/article/{id_article}/criterion', name: 'app_article_criterion', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository, ArticleCriterionRepository $articleCriterionRepository, CriterionLanguageRepository $criterionLanguageRepository, ArticleCriterionValueDescriptionLanguageRepository $articleCriterionValueDescriptionLanguageRepository, int $id_article)
    {
        $criterions = $articleCriterionRepository->findBy(['id_article' => $id_article]);
        if(count($criterions) == 0) return new JsonResponse(['message' => 'Nie znaleziono kryteriów dla artykułu o podanym id'], 404);
        foreach ($criterions as $index=>$criterion) {
            $criterions[$index]->translations = $articleCriterionValueDescriptionLanguageRepository->findBy(['id_article_criterion' => $criterion->getId()]);
        }
        return $this->json($criterions);
    }

    /**
     * Dodaje nowe kryterium do artykulu
     */
    #[OA\Tag(name: "Article")]
    #[OA\RequestBody(
        description: "Kryterium artykułu",
        required: true,
        content: new Model(type: ArticleCriterionCreateRequest::class)
    )]
    #[OA\Response(
        response: 200,
        description: "Dodane kryterium do artykułu",
        content: new Model(type: ArticleCriterionResponse::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono artykułu/kryterium o podanym id"
    )]
    #[OA\Response(
        response: 400,
        description: "Dla danego artykułu już istnieje kryterium o podanym id"
    )]
    #[Route('/article/criterion', name: 'app_article_criterion_add', methods: ['POST'])]
    public function add(ArticleCriterionRepository $articleCriterionRepository, ArticleRepository $articleRepository, CriterionRepository $criterionRepository, ArticleCriterionValueDescriptionLanguageRepository $articleCriterionValueDescriptionLanguageRepository, Request $request)
    {
        $requestArray = $request->toArray();
        $article = $articleRepository->findOneBy(['id' => $requestArray['id_article']]);
        if ($article == null) return new JsonResponse(['message' => 'Nie znaleziono artykulu o podanym id'], 404);
        $criterion = $criterionRepository->findOneBy(['id' => $requestArray['id_criterion']]);
        if($criterion == null) return new JsonResponse(['message' => 'Nie znaleziono kryterium o podanym id'], 404);
        $thisArticleCriterion = $articleCriterionRepository->findOneBy(['id_article' => $requestArray['id_article'], 'id_criterion' => $requestArray['id_criterion']]);
        if($thisArticleCriterion !== null) return new JsonResponse(['message' => 'Dla tego artykułu już istnieje kryterium o podanym id'], 400);
        $articleCriterion = new ArticleCriterion();
        $articleCriterion->setIdCriterion($requestArray['id_criterion']);
        $articleCriterion->setIdArticle($requestArray['id_article']);
        $articleCriterion->setValue($requestArray['value']);
        $articleCriterion->setValueDescription($requestArray['value_description']);
        $articleCriterionRepository->save($articleCriterion,true);
        /* Jeżeli przekazano tłumaczenia value_description to zapisywanie ich */
        $articleCriterionTranslations = [];
        if(isset($requestArray['translations']) && count($requestArray['translations']) > 0) {
            foreach ($requestArray['translations'] as $translation) {
                $valueDescriptionTranslation = new ArticleCriterionValueDescriptionLanguage();
                $valueDescriptionTranslation->setIdLanguage($translation['id_language']);
                $valueDescriptionTranslation->setIdArticleCriterion($articleCriterion->getId());
                $valueDescriptionTranslation->setValueDescription($translation['value_description']);
                $articleCriterionValueDescriptionLanguageRepository->save($valueDescriptionTranslation, true);
                $articleCriterionTranslations[] = $valueDescriptionTranslation;
            }
        }
        $data = array_merge((array) $articleCriterion, ["translations" => $articleCriterionTranslations]);
        return new JsonResponse($data);
    }

    /**
     * Aktualizuje kryterium artykułu
     */
    #[OA\Tag(name: "Article")]
    #[OA\RequestBody(
        description: "Kryterium artykułu",
        required: true,
        content: new Model(type: ArticleCriterionUpdateRequest::class)
    )]
    #[OA\Response(
        response: 200,
        description: "Zaktualizowane kryterium artykułu",
        content: new Model(type: ArticleCriterionResponse::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono artykułu/kryterium o podanym id"
    )]
    #[Route('/article/criterion', name: 'app_article_criterion_update', methods: ['PUT'])]
    public function update(ArticleCriterionRepository $articleCriterionRepository, ArticleRepository $articleRepository, CriterionRepository $criterionRepository, ArticleCriterionValueDescriptionLanguageRepository $articleCriterionValueDescriptionLanguageRepository, Request $request)
    {
        $requestArray = $request->toArray();
        $article = $articleRepository->findOneBy(['id' => $requestArray['id_article']]);
        if ($article == null) return new JsonResponse(['message' => 'Nie znaleziono artykulu o podanym id'], 404);
        $criterion = $criterionRepository->findOneBy(['id' => $requestArray['id_criterion']]);
        if($criterion == null) return new JsonResponse(['message' => 'Nie znaleziono kryterium o podanym id'], 404);
        $thisArticleCriterion = $articleCriterionRepository->findOneBy(['id_article' => $requestArray['id_article'], 'id_criterion' => $requestArray['id_criterion']]);
        // if($thisArticleCriterion !== null) return $this->json(['error' => 'Dla tego artykułu już istnieje kryterium o podanym id']);
        $articleCriterion = $articleCriterionRepository->findOneBy(['id' => $requestArray['id']]);
        $articleCriterion->setIdCriterion($requestArray['id_criterion']);
        $articleCriterion->setIdArticle($requestArray['id_article']);
        $articleCriterion->setValue($requestArray['value']);
        $articleCriterion->setValueDescription($requestArray['value_description']);
        $articleCriterionRepository->save($articleCriterion,true);
        /* Jeżeli przekazano tłumaczenia value_description to zapisywanie ich */
        $articleCriterionTranslations = [];
        if(isset($requestArray['translations']) && count($requestArray['translations']) > 0) {
            foreach ($requestArray['translations'] as $translation) {
                if(isset($translation['id'])) {
                    $valueDescriptionTranslation = $articleCriterionValueDescriptionLanguageRepository->findOneBy(['id' => $translation['id']]);
                }else{
                    $valueDescriptionTranslation = new ArticleCriterionValueDescriptionLanguage();
                }
                $valueDescriptionTranslation->setIdLanguage($translation['id_language']);
                $valueDescriptionTranslation->setIdArticleCriterion($articleCriterion->getId());
                $valueDescriptionTranslation->setValueDescription($translation['value_description']);
                $articleCriterionValueDescriptionLanguageRepository->save($valueDescriptionTranslation, true);
                $articleCriterionTranslations[] = $valueDescriptionTranslation;
            }
        }
        $data = array_merge((array) $articleCriterion, ["translations" => $articleCriterionTranslations]);
        return new JsonResponse($data);
    }


    /**
     * Usuwa kryterium z artykułu
     */
    #[OA\Tag(name: "Article")]
    #[OA\Response(
        response: 200,
        description: "Usunięto",
        content: new Model(type: MessageResponse::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono kryterium/artykułu o podanym id"
    )]
    #[Route('/article/criterion/{id_article_criterion}', name: 'app_article_criterion_delete', methods: ["DELETE"])]
    public function delete(ArticleCriterionRepository $articleCriterionRepository, ArticleRepository $articleRepository, CriterionRepository $criterionRepository, int $id_article_criterion): JsonResponse
    {
        $criterion = $articleCriterionRepository->findOneBy(['id' => $id_article_criterion]);
        if($criterion == null) return new JsonResponse(['message' => 'Nie istnieje takie kryterium'], 404);
        $articleCriterionRepository->remove($criterion, true);
        return new JsonResponse(["message" => "Usunięto"]);
    }

}
