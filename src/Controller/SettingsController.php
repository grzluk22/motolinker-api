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
        summary: 'Zwraca wszystkie ustawienia w formacie klucz-wartość.',
        tags: ['Settings']
    )]
    #[OA\Response(
        response: 200,
        description: 'Zwraca wszystkie ustawienia.',
        content: new OA\JsonContent(
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'string')
        )
    )]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Nieautoryzowany.'], 401);
        }
        return $this->json($this->settingRepository->getSettingsAsArray($user));
    }

    #[Route('/{key}', name: 'api_settings_get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/settings/{key}',
        summary: 'Zwraca konkretną wartość ustawienia.',
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
        description: 'Zwraca konkretną wartość ustawienia.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'key', type: 'string'),
                new OA\Property(property: 'value', type: 'string', nullable: true)
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Ustawienie nie znalezione.'
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
        summary: 'Aktualizuje lub tworzy ustawienia.',
        description: 'Aktualizuje lub tworzy ustawienia.',
        tags: ['Settings']
    )]
    #[OA\RequestBody(
        description: 'Ustawienia do aktualizacji (pary klucz-wartość)',
        content: new OA\JsonContent(
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'string')
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Ustawienia zaktualizowane pomyślnie.'
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
        summary: 'Usuwa ustawienie.',
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
        description: 'Ustawienie usunięte pomyślnie.'
    )]
    #[OA\Response(
        response: 404,
        description: 'Ustawienie nie znalezione.'
    )]
    public function delete(string $key): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $success = $this->settingRepository->deleteSetting($key, $user);
        if (!$success) {
            return $this->json(['error' => 'Ustawienie nie znalezione.'], 404);
        }

        return $this->json(['status' => 'success']);
    }
}
