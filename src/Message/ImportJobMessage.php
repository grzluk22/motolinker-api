<?php

namespace App\Message;

class ImportJobMessage
{
    public function __construct(
        private int $importJobId
    ) {
    }

    public function getImportJobId(): int
    {
        return $this->importJobId;
    }
}
