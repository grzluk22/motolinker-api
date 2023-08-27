<?php

namespace App\Entity;

use App\Repository\ArticleCriterionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleCriterionRepository::class)]
class ArticleCriterion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public ?int $id_article = null;

    #[ORM\Column]
    public ?int $id_criterion = null;

    #[ORM\Column(length: 255)]
    public ?string $value = null;

    #[ORM\Column(length: 255)]
    public ?string $value_description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdArticle(): ?int
    {
        return $this->id_article;
    }

    public function setIdArticle(int $id_article): static
    {
        $this->id_article = $id_article;

        return $this;
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

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getValueDescription(): ?string
    {
        return $this->value_description;
    }

    public function setValueDescription(string $value_description): static
    {
        $this->value_description = $value_description;

        return $this;
    }
}
