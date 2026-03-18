<?php

namespace App\Entity;

use App\Repository\ImportMappingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportMappingRepository::class)]
class ImportMapping
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $name = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isDefault = false;

    #[ORM\Column(type: Types::JSON)]
    private array $mappingData = [];

    #[ORM\Column(length: 255, options: ['default' => 'article_code'])]
    private ?string $uniquenessField = 'article_code';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $onDuplicateAction = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $fieldsToUpdate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getMappingData(): array
    {
        return $this->mappingData;
    }

    public function setMappingData(array $mappingData): static
    {
        $this->mappingData = $mappingData;

        return $this;
    }

    public function getUniquenessField(): ?string
    {
        return $this->uniquenessField;
    }

    public function setUniquenessField(string $uniquenessField): static
    {
        $this->uniquenessField = $uniquenessField;

        return $this;
    }

    public function getOnDuplicateAction(): ?string
    {
        return $this->onDuplicateAction;
    }

    public function setOnDuplicateAction(?string $onDuplicateAction): static
    {
        $this->onDuplicateAction = $onDuplicateAction;

        return $this;
    }

    public function getFieldsToUpdate(): ?array
    {
        return $this->fieldsToUpdate;
    }

    public function setFieldsToUpdate(?array $fieldsToUpdate): static
    {
        $this->fieldsToUpdate = $fieldsToUpdate;

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
}
