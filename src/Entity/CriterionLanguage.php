<?php

namespace App\Entity;

use App\Repository\CriterionLanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CriterionLanguageRepository::class)]
class CriterionLanguage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public ?int $id_criterion = null;

    #[ORM\Column]
    public ?int $id_language = null;

    #[ORM\Column(length: 255)]
    public ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdCriterion(): ?int
    {
        return $this->id_criterion;
    }

    public function setIdCriterion(int $id_criterion): static
    {
        $this->id_criterion = $id_criterion;

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
}
