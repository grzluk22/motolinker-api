<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserGroup;
use App\HttpRequestModel\UserCreateRequest;
use App\HttpRequestModel\UserUpdateRequest;
use App\HttpResponseModel\UserResponse;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Users')]
class UserController extends AbstractController
{
    #[Route('', name: 'app_user_index', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Zwraca listę użytkowników.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: UserResponse::class)))
    )]
    public function index(UserRepository $repository): Response
    {
        $users = $repository->findAll();
        $response = [];

        foreach ($users as $user) {
            $response[] = $this->mapToResponse($user);
        }

        return $this->json($response);
    }

    #[Route('', name: 'app_user_create', methods: ['POST'])]
    #[OA\RequestBody(content: new Model(type: UserCreateRequest::class))]
    #[OA\Response(
        response: 201,
        description: 'Użytkownik utworzony pomyślnie.',
        content: new Model(type: UserResponse::class)
    )]
    public function create(
        Request $request,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $data = json_decode($request->getContent(), true);
        $em = $doctrine->getManager();

        $user = new User();
        $user->setEmail($data['email']);
        $user->setUsername($data['username']);
        $user->setRoles($data['roles'] ?? []);

        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        if (isset($data['userGroupIds'])) {
            foreach ($data['userGroupIds'] as $groupId) {
                $group = $em->getRepository(UserGroup::class)->find($groupId);
                if ($group) {
                    $user->addUserGroup($group);
                }
            }
        }

        $em->persist($user);
        $em->flush();

        return $this->json($this->mapToResponse($user), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Zwraca pojedynczego użytkownika.',
        content: new Model(type: UserResponse::class)
    )]
    public function show(User $user): Response
    {
        return $this->json($this->mapToResponse($user));
    }

    #[Route('/{id}', name: 'app_user_update', methods: ['PUT'])]
    #[OA\RequestBody(content: new Model(type: UserUpdateRequest::class))]
    #[OA\Response(
        response: 200,
        description: 'Użytkownik zaktualizowany pomyślnie.',
        content: new Model(type: UserResponse::class)
    )]
    public function update(
        Request $request,
        User $user,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $data = json_decode($request->getContent(), true);
        $em = $doctrine->getManager();

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['username'])) {
            $user->setUsername($data['username']);
        }
        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
        }
        if (isset($data['password']) && !empty($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        if (isset($data['userGroupIds'])) {
            // Clear existing groups
            foreach ($user->getUserGroups() as $group) {
                $user->removeUserGroup($group);
            }
            // Add new groups
            foreach ($data['userGroupIds'] as $groupId) {
                $group = $em->getRepository(UserGroup::class)->find($groupId);
                if ($group) {
                    $user->addUserGroup($group);
                }
            }
        }

        $em->flush();

        return $this->json($this->mapToResponse($user));
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'Użytkownik usunięty pomyślnie.')]
    public function delete(User $user, UserRepository $repository): Response
    {
        $repository->remove($user, true);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function mapToResponse(User $user): UserResponse
    {
        $response = new UserResponse();
        $response->id = $user->getId();
        $response->email = $user->getEmail();
        $response->username = $user->getUsername();
        $response->roles = $user->getRoles();
        $response->userGroupIds = [];
        foreach ($user->getUserGroups() as $group) {
            $response->userGroupIds[] = $group->getId();
        }

        return $response;
    }
}
