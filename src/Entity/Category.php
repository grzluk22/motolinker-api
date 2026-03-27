<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public ?int $id_parent = null;

    #[ORM\Column]
    public ?int $products_count = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdParent(): ?int
    {
        return $this->id_parent;
    }

    public function setIdParent(int $id_parent): static
    {
        $this->id_parent = $id_parent;

        return $this;
    }

    public function getProductsCount(): ?int
    {
        return $this->products_count;
    }

    public function setProductsCount(?int $products_count): static
    {
        $this->products_count = $products_count;

        return $this;
    }
}
