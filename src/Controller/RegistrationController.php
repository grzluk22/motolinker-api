<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use App\Entity\UserGroup;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use App\HttpRequestModel\RegistrationRequest;
use App\HttpResponseModel\RegistrationResponse;

class RegistrationController extends AbstractController
{
    /**
     * Pozwala na rejestrację nowego użytkownika
     */
    #[OA\Tag(name: "Auth")]
    #[OA\RequestBody(
        description: "Login oraz hasło do rejestracji",
        required: true,
        content: new Model(type: RegistrationRequest::class)
    )]
    #[OA\Response(
        response: 200,
        description: "Zarejestrowano pomyślnie",
        content: new Model(type: RegistrationResponse::class)
    )]
    #[Route('/register', name: 'app_register', methods: ["POST"])]
    public function index(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {

        $em = $doctrine->getManager();
        $decoded = json_decode($request->getContent());
        $email = $decoded->username;
        $plaintextPassword = $decoded->password;

        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser !== null) {
            return $this->json(
                ['message' => 'Użytkownik z takim adresem e-mail już istnieje.'],
                Response::HTTP_CONFLICT
            );
        }

        $user = new User();
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setPassword($hashedPassword);
        $user->setEmail($email);
        $user->setUsername($email);

        $defaultGroups = $em->getRepository(UserGroup::class)->findBy(['isDefault' => true]);
        foreach ($defaultGroups as $group) {
            $user->addUserGroup($group);
        }

        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'Registered Successfully']);
    }
}