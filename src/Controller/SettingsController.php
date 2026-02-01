<?php

namespace App\Controller;

use App\Repository\SettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/settings')]
#[OA\Tag(name: 'Settings')]
class SettingsController extends AbstractController
{
    public function __construct(
        private readonly SettingRepository $settingRepository
    ) {
    }

    #[Route('', name: 'api_settings_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/settings',
        summary: 'Returns all settings as key-value pairs',
        tags: ['Settings']
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns all settings',
        content: new OA\JsonContent(
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'string')
        )
    )]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        return $this->json($this->settingRepository->getSettingsAsArray($user));
    }

    #[Route('/{key}', name: 'api_settings_get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/settings/{key}',
        summary: 'Returns a specific setting value',
        tags: ['Settings']
    )]
    #[OA\Parameter(
        name: 'key',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns a specific setting value',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'key', type: 'string'),
                new OA\Property(property: 'value', type: 'string', nullable: true)
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Setting not found'
    )]
    public function get(string $key): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $value = $this->settingRepository->getSetting($key, $user);
        if ($value === null) {
            return $this->json(['error' => 'Setting not found'], 404);
        }

        return $this->json(['key' => $key, 'value' => $value]);
    }

    #[Route('', name: 'api_settings_update', methods: ['POST', 'PUT'])]
    #[OA\Post(
        path: '/api/settings',
        summary: 'Update settings',
        description: 'Updates or creates multiple settings at once',
        tags: ['Settings']
    )]
    #[OA\RequestBody(
        description: 'Settings to update (key-value pairs)',
        content: new OA\JsonContent(
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'string')
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Settings updated successfully'
    )]
    public function update(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        foreach ($data as $key => $value) {
            // Basic validation for key
            if (empty($key) || !is_string($key)) {
                continue;
            }

            // Normalization is handled in Entity
            $this->settingRepository->updateSetting((string) $key, $value !== null ? (string) $value : null, $user);
        }

        return $this->json(['status' => 'success']);
    }

    #[Route('/{key}', name: 'api_settings_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/settings/{key}',
        summary: 'Delete a setting',
        tags: ['Settings']
    )]
    #[OA\Parameter(
        name: 'key',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Setting deleted successfully'
    )]
    #[OA\Response(
        response: 404,
        description: 'Setting not found'
    )]
    public function delete(string $key): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $success = $this->settingRepository->deleteSetting($key, $user);
        if (!$success) {
            return $this->json(['error' => 'Setting not found'], 404);
        }

        return $this->json(['status' => 'success']);
    }
}
