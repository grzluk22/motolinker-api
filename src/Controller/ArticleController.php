<?php

namespace App\Controller;

use App\Entity\Article;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    /**
     * Wyświetla liste artykułów
     *
     * Dodatkowy opis metody do NelmioApiDocBundle
     */
    #[Route('/article', name: 'app_article', methods: ["GET"])]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ArticleController.php',
        ]);
    }

    /**
     * Tworzy nowy artykuł
     *
     * Przyjmuje pola arykułu
     */
    #[Route('/article', name: 'app_article', methods: ["POST"])]
    public function create(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $entityManager = $doctrine->getManager();

        /* Ustawianie podstawowych danych artykułu */
        $article = new Article();
        $article->setCode($request->request->get('code'));
        $article->setEan13($request->request->get('ean13'));
        $article->setPrice($request->request->get('price'));
        $article->setIdCategory($request->request->get('id_category'));
        /* Ustawianie tłumaczeń, przynajmniej jedno powinno być przekazane, w przypadku braku takiego języka w bazie danych tworzenie nowego  */



        $entityManager->persist($article);
        $entityManager->flush();

        $data =  [
            'id' => $article->getId(),
            'code' => $article->getCode()
        ];

        return $this->json($data);
    }
}
