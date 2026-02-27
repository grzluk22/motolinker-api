<?php

namespace App\Controller;

use App\Entity\UserGroup;
use App\HttpRequestModel\UserGroupRequest;
use App\HttpResponseModel\UserGroupResponse;
use App\Repository\UserGroupRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/user-groups')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'User Groups')]
class UserGroupController extends AbstractController
{
    #[Route('', name: 'app_user_group_index', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Zwraca listę grup użytkowników.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: UserGroupResponse::class)))
    )]
    public function index(UserGroupRepository $repository): Response
    {
        $groups = $repository->findAll();
        $response = [];

        foreach ($groups as $group) {
            $model = new UserGroupResponse();
            $model->id = $group->getId();
            $model->name = $group->getName();
            $model->roles = $group->getRoles();
            $model->isDefault = $group->isDefault();
            $response[] = $model;
        }

        return $this->json($response);
    }

    #[Route('', name: 'app_user_group_create', methods: ['POST'])]
    #[OA\RequestBody(content: new Model(type: UserGroupRequest::class))]
    #[OA\Response(
        response: 201,
        description: 'Grupa utworzona pomyślnie.',
        content: new Model(type: UserGroupResponse::class)
    )]
    public function create(Request $request, UserGroupRepository $repository): Response
    {
        $data = json_decode($request->getContent(), true);

        $group = new UserGroup();
        $group->setName($data['name']);
        $group->setRoles($data['roles'] ?? []);
        $group->setIsDefault($data['isDefault'] ?? false);

        $repository->save($group, true);

        $response = new UserGroupResponse();
        $response->id = $group->getId();
        $response->name = $group->getName();
        $response->roles = $group->getRoles();
        $response->isDefault = $group->isDefault();

        return $this->json($response, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_user_group_show', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Zwraca pojedynczą grupę użytkowników.',
        content: new Model(type: UserGroupResponse::class)
    )]
    public function show(UserGroup $group): Response
    {
        $response = new UserGroupResponse();
        $response->id = $group->getId();
        $response->name = $group->getName();
        $response->roles = $group->getRoles();
        $response->isDefault = $group->isDefault();

        return $this->json($response);
    }

    #[Route('/{id}', name: 'app_user_group_update', methods: ['PUT'])]
    #[OA\RequestBody(content: new Model(type: UserGroupRequest::class))]
    #[OA\Response(
        response: 200,
        description: 'Grupa zaktualizowana pomyślnie.',
        content: new Model(type: UserGroupResponse::class)
    )]
    public function update(Request $request, UserGroup $group, UserGroupRepository $repository): Response
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $group->setName($data['name']);
        }
        if (isset($data['roles'])) {
            $group->setRoles($data['roles']);
        }
        if (isset($data['isDefault'])) {
            $group->setIsDefault($data['isDefault']);
        }

        $repository->save($group, true);

        $response = new UserGroupResponse();
        $response->id = $group->getId();
        $response->name = $group->getName();
        $response->roles = $group->getRoles();
        $response->isDefault = $group->isDefault();

        return $this->json($response);
    }

    #[Route('/{id}', name: 'app_user_group_delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'Grupa usunięta pomyślnie.')]
    public function delete(UserGroup $group, UserGroupRepository $repository): Response
    {
        $repository->remove($group, true);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
