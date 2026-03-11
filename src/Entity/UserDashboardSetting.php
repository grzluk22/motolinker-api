<?php

namespace App\Entity;

use App\Repository\UserDashboardSettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserDashboardSettingRepository::class)]
#[ORM\Table(name: 'user_dashboard_settings', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'user_widget_idx', columns: ['user_id', 'widget_id'])
])]
class UserDashboardSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $widgetId = null;

    #[ORM\Column]
    private ?bool $isVisible = true;

    #[ORM\Column]
    private ?int $gridX = 0;

    #[ORM\Column]
    private ?int $gridY = 0;

    #[ORM\Column]
    private ?int $gridCols = 1;

    #[ORM\Column]
    private ?int $gridRows = 1;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: true)]
    private ?array $config = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getWidgetId(): ?string
    {
        return $this->widgetId;
    }

    public function setWidgetId(string $widgetId): static
    {
        $this->widgetId = $widgetId;

        return $this;
    }

    public function isVisible(): ?bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): static
    {
        $this->isVisible = $isVisible;

        return $this;
    }

    public function getGridX(): ?int
    {
        return $this->gridX;
    }

    public function setGridX(int $gridX): static
    {
        $this->gridX = $gridX;

        return $this;
    }

    public function getGridY(): ?int
    {
        return $this->gridY;
    }

    public function setGridY(int $gridY): static
    {
        $this->gridY = $gridY;

        return $this;
    }

    public function getGridCols(): ?int
    {
        return $this->gridCols;
    }

    public function setGridCols(int $gridCols): static
    {
        $this->gridCols = $gridCols;

        return $this;
    }

    public function getGridRows(): ?int
    {
        return $this->gridRows;
    }

    public function setGridRows(int $gridRows): static
    {
        $this->gridRows = $gridRows;

        return $this;
    }

    public function getConfig(): ?array
    {
        return $this->config;
    }

    public function setConfig(?array $config): static
    {
        $this->config = $config;

        return $this;
    }
}
