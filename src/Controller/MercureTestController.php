<?php

namespace App\Controller;

use App\Entity\ImportJob;
use App\Repository\ImportJobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/test', name: 'api_test_')]
class MercureTestController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ImportJobRepository $importJobRepository,
        private HubInterface $hub
    ) {
    }

    #[Route('/mercure/{id}', name: 'mercure_test', methods: ['GET'])]
    public function testMercure(int $id): JsonResponse
    {
        $job = $this->importJobRepository->find($id);

        if (!$job) {
            return new JsonResponse(['error' => 'Job not found. Proszę podać ID istniejącego importu.'], 404);
        }

        $steps = 5;
        $updatesSent = 0;
        $startRows = $job->getProcessedRows();

        for ($i = 1; $i <= $steps; $i++) {
            // 1. Aktualizacja bazy danych (Persystencja)
            $newProgress = $job->getProcessedRows() + 10;
            $job->setProcessedRows($newProgress);
            $this->entityManager->flush();

            // 2. Publikacja przez Mercure (Real-time)
            try {
                $update = new Update(
                    'https://motolinker.local/import/progress/' . $job->getId(),
                    json_encode([
                        'status' => 'processing',
                        'processed' => $job->getProcessedRows(),
                        'total' => $job->getTotalRows() ?: 100,
                        'test_simulated' => true
                    ])
                );
                $this->hub->publish($update);
                $updatesSent++;
            } catch (\Exception $e) {
                // W teście ignorujemy błędy połączenia z hubem, ale zwrócimy informację o błędzie
                return new JsonResponse([
                    'error' => 'Mercure error: ' . $e->getMessage(),
                    'hint' => 'Upewnij się, że Hub Mercure jest uruchomiony.'
                ], 500);
            }

            usleep(500000); // 0.5s opóźnienia, żeby symulacja była widoczna
        }

        return new JsonResponse([
            'message' => "Symulacja zakończona pomyślnie",
            'jobId' => $id,
            'steps_simulated' => $steps,
            'rows_before' => $startRows,
            'rows_after' => $job->getProcessedRows(),
            'mercure_updates_sent' => $updatesSent,
            'topic' => 'https://motolinker.local/import/progress/' . $job->getId()
        ]);
    }
}
