<?php

namespace App\Entity;

use App\Repository\ImageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $filename = null;

    #[ORM\Column(length: 255)]
    public ?string $original_filename = null;

    #[ORM\Column]
    public ?int $file_size = null;

    #[ORM\Column(length: 100)]
    public ?string $mime_type = null;

    #[ORM\Column]
    public ?int $width = null;

    #[ORM\Column]
    public ?int $height = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public ?bool $is_main = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    public ?\DateTimeInterface $created_at = null;

    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'images')]
    #[ORM\JoinColumn(name: 'id_article', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public ?Article $article = null;

    #[ORM\Column]
    public ?int $position = null;

    #[ORM\Column(length: 255)]
    public ?string $url = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->created_at === null) {
            $this->created_at = new \DateTime();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->original_filename;
    }

    public function setOriginalFilename(string $original_filename): static
    {
        $this->original_filename = $original_filename;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->file_size;
    }

    public function setFileSize(int $file_size): static
    {
        $this->file_size = $file_size;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mime_type;
    }

    public function setMimeType(string $mime_type): static
    {
        $this->mime_type = $mime_type;

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function isMain(): ?bool
    {
        return $this->is_main;
    }

    public function setIsMain(bool $is_main): static
    {
        $this->is_main = $is_main;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;

        return $this;
    }

    public function getIdArticle(): ?int
    {
        return $this->article?->getId();
    }

    public function setIdArticle(int $id_article): static
    {
        // This method is kept for backward compatibility but should use setArticle instead
        // The actual article should be set via setArticle()
        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }
}
