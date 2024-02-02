<?php

namespace App\Controller;

use App\Repository\ArticleLanguageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class ArticleLanguageController extends AbstractController
{
    /**
     * Usuwa tłumaczenie dla artykułu
     *
     * @OA\Tag(name="ArticleLanguage")
     * @OA\Response(
     *     response=200,
     *     description="Usunięto"
     * )
     * @OA\Response(
     *     response=404,
     *     description="Nie znaleziono tłumaczenia o podanym id"
     * )
     **/
    #[Route('/article/language/{id}', name: 'app_article_language_delete', methods: ['DELETE'])]
    public function delete(ArticleLanguageRepository $articleLanguageRepository, string $id)
    {
        $articleLanguage = $articleLanguageRepository->findOneBy(['id' => $id]);
        if($articleLanguage === null) {
            return $this->json(["error" => "Nie znaleziono tłumaczenia o podanym kodzie"]);
        }
        $articleLanguageRepository->remove($articleLanguage, true);
        return $this->json("Usunięto");
    }
}
