<?php

namespace App\MessageHandler;

use App\Message\ImportJobMessage;
use App\Service\ImportService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ImportJobHandler
{
    public function __construct(
        private ImportService $importService
    ) {
    }

    public function __invoke(ImportJobMessage $message): void
    {
        $this->importService->processJob($message->getImportJobId());
    }
}
