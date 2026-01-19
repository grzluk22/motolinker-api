<?php

namespace App\Entity;

use App\Repository\ImportJobRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportJobRepository::class)]
class ImportJob
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 255)]
    private ?string $filePath = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $processedOffset = '0';

    #[ORM\Column]
    private ?int $processedRows = 0;

    #[ORM\Column(nullable: true)]
    private ?int $totalRows = null;

    #[ORM\Column(type: Types::JSON)]
    private array $mapping = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?string $articleIdentifierField = null;

    #[ORM\Column(length: 50, options: ['default' => 'articles'])]
    private string $importType = 'articles';

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'created';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getProcessedOffset(): int
    {
        return (int) $this->processedOffset;
    }

    public function setProcessedOffset(int $processedOffset): static
    {
        $this->processedOffset = (string) $processedOffset;

        return $this;
    }

    public function getProcessedRows(): ?int
    {
        return $this->processedRows;
    }

    public function setProcessedRows(int $processedRows): static
    {
        $this->processedRows = $processedRows;

        return $this;
    }

    public function getTotalRows(): ?int
    {
        return $this->totalRows;
    }

    public function setTotalRows(?int $totalRows): static
    {
        $this->totalRows = $totalRows;

        return $this;
    }

    public function getMapping(): array
    {
        return $this->mapping;
    }

    public function setMapping(array $mapping): static
    {
        $this->mapping = $mapping;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getArticleIdentifierField(): ?string
    {
        return $this->articleIdentifierField;
    }

    public function setArticleIdentifierField(?string $articleIdentifierField): static
    {
        $this->articleIdentifierField = $articleIdentifierField;

        return $this;
    }

    public function getImportType(): string
    {
        return $this->importType;
    }

    public function setImportType(string $importType): static
    {
        $this->importType = $importType;

        return $this;
    }
}
