<?php

namespace App\Entity;

use App\Repository\ArticleCriterionValueDescriptionLanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleCriterionValueDescriptionLanguageRepository::class)]
class ArticleCriterionValueDescriptionLanguage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $id_article_criterion = null;

    #[ORM\Column]
    private ?int $id_language = null;

    #[ORM\Column(length: 255)]
    private ?string $value_description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdArticleCriterion(): ?int
    {
        return $this->id_article_criterion;
    }

    public function setIdArticleCriterion(int $id_article_criterion): static
    {
        $this->id_article_criterion = $id_article_criterion;

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
