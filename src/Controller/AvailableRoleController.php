<?php

namespace App\Controller;

use App\Entity\AvailableRole;
use App\HttpRequestModel\AvailableRoleRequest;
use App\HttpResponseModel\AvailableRoleResponse;
use App\Repository\AvailableRoleRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/user_roles')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Available Roles')]
class AvailableRoleController extends AbstractController
{
    #[Route('', name: 'app_available_role_index', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Returns the list of available roles',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: AvailableRoleResponse::class)))
    )]
    public function index(AvailableRoleRepository $repository): Response
    {
        $roles = $repository->findAll();
        $response = [];

        foreach ($roles as $role) {
            $model = new AvailableRoleResponse();
            $model->id = $role->getId();
            $model->name = $role->getName();
            $model->description = $role->getDescription();
            $response[] = $model;
        }

        return $this->json($response);
    }

    #[Route('', name: 'app_available_role_create', methods: ['POST'])]
    #[OA\RequestBody(content: new Model(type: AvailableRoleRequest::class))]
    #[OA\Response(
        response: 201,
        description: 'Role created',
        content: new Model(type: AvailableRoleResponse::class)
    )]
    public function create(Request $request, AvailableRoleRepository $repository): Response
    {
        $data = json_decode($request->getContent(), true);

        $role = new AvailableRole();
        $role->setName($data['name']);
        $role->setDescription($data['description'] ?? null);

        $repository->save($role, true);

        $response = new AvailableRoleResponse();
        $response->id = $role->getId();
        $response->name = $role->getName();
        $response->description = $role->getDescription();

        return $this->json($response, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_available_role_show', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Returns a single role',
        content: new Model(type: AvailableRoleResponse::class)
    )]
    public function show(AvailableRole $role): Response
    {
        $response = new AvailableRoleResponse();
        $response->id = $role->getId();
        $response->name = $role->getName();
        $response->description = $role->getDescription();

        return $this->json($response);
    }

    #[Route('/{id}', name: 'app_available_role_update', methods: ['PUT'])]
    #[OA\RequestBody(content: new Model(type: AvailableRoleRequest::class))]
    #[OA\Response(
        response: 200,
        description: 'Role updated',
        content: new Model(type: AvailableRoleResponse::class)
    )]
    public function update(Request $request, AvailableRole $role, AvailableRoleRepository $repository): Response
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $role->setName($data['name']);
        }
        if (isset($data['description'])) {
            $role->setDescription($data['description']);
        }

        $repository->save($role, true);

        $response = new AvailableRoleResponse();
        $response->id = $role->getId();
        $response->name = $role->getName();
        $response->description = $role->getDescription();

        return $this->json($response);
    }

    #[Route('/{id}', name: 'app_available_role_delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'Role deleted')]
    public function delete(AvailableRole $role, AvailableRoleRepository $repository): Response
    {
        $repository->remove($role, true);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
