<?php

namespace App\Entity;

use App\Repository\ArticleCarRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleCarRepository::class)]
class ArticleCar
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public ?int $id_article = null;

    #[ORM\Column]
    public ?int $id_car = null;

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

    public function getIdCar(): ?int
    {
        return $this->id_car;
    }

    public function setIdCar(int $id_car): static
    {
        $this->id_car = $id_car;

        return $this;
    }
}
