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
    #[OA\Response(
        response: 200,
        description: 'Returns all settings'
    )]
    public function list(): JsonResponse
    {
        return $this->json($this->settingRepository->getSettingsAsArray());
    }

    #[Route('/{key}', name: 'api_settings_get', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Returns a specific setting value'
    )]
    #[OA\Response(
        response: 404,
        description: 'Setting not found'
    )]
    public function get(string $key): JsonResponse
    {
        $value = $this->settingRepository->getSetting($key);
        if ($value === null) {
            return $this->json(['error' => 'Setting not found'], 404);
        }

        return $this->json(['key' => $key, 'value' => $value]);
    }

    #[Route('', name: 'api_settings_update', methods: ['POST', 'PUT'])]
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
        $data = json_decode($request->getContent(), true);
        if (!$data || !is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        foreach ($data as $key => $value) {
            $this->settingRepository->updateSetting((string) $key, $value !== null ? (string) $value : null);
        }

        return $this->json(['status' => 'success']);
    }
}
