<?php

namespace App\Entity;

use App\Repository\ImportJobRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportJobRepository::class)]
class ImportJob
{
    public const STATUS_CREATED = 'created';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PAUSING = 'pausing';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_CANCELLING = 'cancelling';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REVERTING = 'reverting';
    public const STATUS_REVERTED = 'reverted';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $processedOffset = '0';

    #[ORM\Column]
    private ?int $processedRows = 0;

    #[ORM\Column(nullable: true)]
    private ?int $totalRows = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $mapping = [];

    #[ORM\Column(length: 50)]
    private string $source = 'csv';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $sourceIds = [];

    #[ORM\ManyToOne(targetEntity: ExternalDatabase::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ExternalDatabase $externalDatabase = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?string $articleIdentifierField = null;

    #[ORM\Column(length: 50, options: ['default' => 'articles'])]
    private string $importType = 'articles';

    #[ORM\Column(nullable: true)]
    private ?int $debugDelay = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = self::STATUS_CREATED;
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

    public function getDebugDelay(): ?int
    {
        return $this->debugDelay;
    }

    public function setDebugDelay(?int $debugDelay): static
    {
        $this->debugDelay = $debugDelay;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function getSourceIds(): ?array
    {
        return $this->sourceIds;
    }

    public function setSourceIds(?array $sourceIds): static
    {
        $this->sourceIds = $sourceIds;
        return $this;
    }

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $sourceFilters = [];

    public function getSourceFilters(): ?array
    {
        return $this->sourceFilters;
    }

    public function setSourceFilters(?array $sourceFilters): static
    {
        $this->sourceFilters = $sourceFilters;
        return $this;
    }

    public function getExternalDatabase(): ?ExternalDatabase
    {
        return $this->externalDatabase;
    }

    public function setExternalDatabase(?ExternalDatabase $externalDatabase): static
    {
        $this->externalDatabase = $externalDatabase;
        return $this;
    }
}
