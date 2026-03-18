<?php

namespace App\MessageHandler;

use App\Message\ImportJobMessage;
use App\Service\ImportService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AsMessageHandler]
class ImportJobHandler
{
    public function __construct(
        private ImportService $importService,
        private \Doctrine\Persistence\ManagerRegistry $managerRegistry,
        private HubInterface $hub,
        private readonly string $mercureTopicBaseUrl
    ) {
    }

    public function __invoke(ImportJobMessage $message): void
    {
        try {
            $this->importService->processJob($message->getImportJobId());
        } catch (\Throwable $e) {
            $em = $this->managerRegistry->getManager();

            if (!$em->isOpen()) {
                $this->managerRegistry->resetManager();
                $em = $this->managerRegistry->getManager();
            }

            /** @var \App\Entity\ImportJob|null $job */
            $job = $em->getRepository(\App\Entity\ImportJob::class)->find($message->getImportJobId());

            if ($job) {
                $job->setStatus(\App\Entity\ImportJob::STATUS_FAILED);
                $job->setErrorMessage($e->getMessage());
                $em->flush();

                // Publish failure event to Mercure
                try {
                    $update = new Update(
                        rtrim($this->mercureTopicBaseUrl, '/') . '/import/progress/' . $job->getId(),
                        json_encode([
                            'status' => \App\Entity\ImportJob::STATUS_FAILED, 
                            'processed' => $job->getProcessedRows(),
                            'total' => $job->getTotalRows(),
                            'error' => $e->getMessage()
                        ])
                    );
                    $this->hub->publish($update);
                } catch (\Throwable $mercureException) {
                    // Ignore Mercure errors here
                }
            }

            // We catch the error to prevent retry loop
        }
    }
}
