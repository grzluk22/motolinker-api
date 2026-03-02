<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;

class MercureTestController extends AbstractController
{
    #[Route('/api/mercure/test', name: 'mercure_test', methods: ['POST'])]
    public function testMercure(Request $request, HubInterface $hub, string $mercureTopicBaseUrl): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $topicSuffix = $data['topic'] ?? '/test/ping';
        $message = $data['message'] ?? 'Hello from Mercure diagnostic tool!';

        $topicUrl = rtrim($mercureTopicBaseUrl, '/') . $topicSuffix;

        $update = new Update(
            $topicUrl,
            json_encode(['message' => $message, 'timestamp' => time()])
        );

        try {
            $hub->publish($update);
            return new JsonResponse(['status' => 'success', 'topic' => $topicUrl, 'published_message' => $message]);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'error_message' => $e->getMessage()], 500);
        }
    }
}
