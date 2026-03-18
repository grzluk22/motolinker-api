<?php

namespace App\Entity;

use App\Repository\CategoryLanguageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryLanguageRepository::class)]
class CategoryLanguage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public ?int $id_category = null;

    #[ORM\Column]
    public ?int $id_language = null;

    #[ORM\Column(length: 255)]
    public ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    public ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdCategory(): ?int
    {
        return $this->id_category;
    }

    public function setIdCategory(int $id_category): static
    {
        $this->id_category = $id_category;

        return $this;
    }

    public function getIdLanguage(): ?int
    {
        return $this->id_language;
    }

    public function setIdLanguage(int $id_language): static
    {
        $this->id_language = $id_language;

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
