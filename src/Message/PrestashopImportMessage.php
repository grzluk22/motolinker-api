<?php

declare(strict_types=1);

namespace App\Message;

class PrestashopImportMessage
{
    public function __construct(
        private readonly int $importJobId
    ) {
    }

    public function getImportJobId(): int
    {
        return $this->importJobId;
    }
}
