<?php

namespace App\Entity;

use App\Repository\ImportRowsAffectedRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportRowsAffectedRepository::class)]
#[ORM\Table(name: 'import_rows_affected')]
class ImportRowsAffected
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(name: 'job_id')]
    private ?int $jobId = null;

    #[ORM\Column(name: '`table`', length: 255)]
    private ?string $table = null;

    #[ORM\Column(name: 'rowID')]
    private ?int $rowId = null;

    #[ORM\Column(name: '`date`', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    public function __construct()
    {
        $this->date = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getJobId(): ?int
    {
        return $this->jobId;
    }

    public function setJobId(int $jobId): static
    {
        $this->jobId = $jobId;

        return $this;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function setTable(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    public function getRowId(): ?int
    {
        return $this->rowId;
    }

    public function setRowId(int $rowId): static
    {
        $this->rowId = $rowId;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }
}
