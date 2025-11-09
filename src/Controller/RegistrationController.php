<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use OpenApi\Annotations as OA;

class RegistrationController extends AbstractController
{
        /**
     * Pozwala na rejestrację nowego użytkownika
     *
     *
     * @OA\Tag(name="Auth")
     * @OA\RequestBody(
     *     request="RegisterRequestBody",
     *     description="Login oraz hasło do rejestracji",
     *     required=true,
     *     @OA\JsonContent(
     *                     example={
     *                              "username": "admin@motolinker.local",
     *                              "password": "superSecretPassword"
     *                     }
     *    )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Zarejestrowano pomyślnie",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                              "message": "Registered Successfully"
     *                     }
     *             )
     *         })
     * )
     **/

    #[Route('/register', name: 'app_register', methods: ["POST"])]
    public function index(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {

        $em = $doctrine->getManager();
        $decoded = json_decode($request->getContent());
        $email = $decoded->username;
        $plaintextPassword = $decoded->password;

        $user = new User();
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setPassword($hashedPassword);
        $user->setEmail($email);
        $user->setUsername($email);
        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'Registered Successfully']);
    }
}