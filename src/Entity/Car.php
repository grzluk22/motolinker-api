<?php

namespace App\Entity;

use App\Repository\CarRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarRepository::class)]
class Car
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $manufacturer = null;

    #[ORM\Column(length: 255)]
    public ?string $model = null;

    #[ORM\Column(length: 255)]
    public ?string $type = null;

    #[ORM\Column(length: 255)]
    public ?string $model_from = null;

    #[ORM\Column(length: 255)]
    public ?string $model_to = null;

    #[ORM\Column(length: 255)]
    public ?string $body_type = null;

    #[ORM\Column(length: 255)]
    public ?string $drive_type = null;

    #[ORM\Column(length: 255)]
    public ?string $displacement_liters = null;

    #[ORM\Column(length: 255)]
    public ?string $displacement_cmm = null;

    #[ORM\Column(length: 255)]
    public ?string $fuel_type = null;

    #[ORM\Column(length: 255)]
    public ?string $kw = null;

    #[ORM\Column(length: 255)]
    public ?string $hp = null;

    #[ORM\Column]
    public ?int $cylinders = null;

    #[ORM\Column(length: 255)]
    public ?string $valves = null;

    #[ORM\Column(length: 255)]
    public ?string $engine_type = null;

    #[ORM\Column(length: 255)]
    public ?string $engine_codes = null;

    #[ORM\Column(length: 255)]
    public ?string $kba = null;

    #[ORM\Column(length: 255)]
    public ?string $text_value = null;

    #[ORM\Column(length: 64, nullable: true)]
    public ?string $hash = null;

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(?string $hash): static
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Calculates hash based on all properties except id and hash.
     */
    public function calculateHash(): string
    {
        $values = [
            $this->manufacturer,
            $this->model,
            $this->type,
            $this->model_from,
            $this->model_to,
            $this->body_type,
            $this->drive_type,
            $this->displacement_liters,
            $this->displacement_cmm,
            $this->fuel_type,
            $this->kw,
            $this->hp,
            $this->cylinders,
            $this->valves,
            $this->engine_type,
            $this->engine_codes,
            $this->kba,
            $this->text_value
        ];

        // Serialize and hash to ensure consistency
        return hash('sha256', serialize($values));
    }

    public function updateHash(): static
    {
        $this->hash = $this->calculateHash();
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    public function setManufacturer(string $manufacturer): static
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getModelFrom(): ?string
    {
        return $this->model_from;
    }

    public function setModelFrom(string $model_from): static
    {
        $this->model_from = $model_from;

        return $this;
    }

    public function getModelTo(): ?string
    {
        return $this->model_to;
    }

    public function setModelTo(string $model_to): static
    {
        $this->model_to = $model_to;

        return $this;
    }

    public function getBodyType(): ?string
    {
        return $this->body_type;
    }

    public function setBodyType(string $body_type): static
    {
        $this->body_type = $body_type;

        return $this;
    }

    public function getDriveType(): ?string
    {
        return $this->drive_type;
    }

    public function setDriveType(string $drive_type): static
    {
        $this->drive_type = $drive_type;

        return $this;
    }

    public function getDisplacementLiters(): ?string
    {
        return $this->displacement_liters;
    }

    public function setDisplacementLiters(string $displacement_liters): static
    {
        $this->displacement_liters = $displacement_liters;

        return $this;
    }

    public function getDisplacementCmm(): ?string
    {
        return $this->displacement_cmm;
    }

    public function setDisplacementCmm(string $displacement_cmm): static
    {
        $this->displacement_cmm = $displacement_cmm;

        return $this;
    }

    public function getFuelType(): ?string
    {
        return $this->fuel_type;
    }

    public function setFuelType(string $fuel_type): static
    {
        $this->fuel_type = $fuel_type;

        return $this;
    }

    public function getKw(): ?string
    {
        return $this->kw;
    }

    public function setKw(string $kw): static
    {
        $this->kw = $kw;

        return $this;
    }

    public function getHp(): ?string
    {
        return $this->hp;
    }

    public function setHp(string $hp): static
    {
        $this->hp = $hp;

        return $this;
    }

    public function getCylinders(): ?int
    {
        return $this->cylinders;
    }

    public function setCylinders(int $cylinders): static
    {
        $this->cylinders = $cylinders;

        return $this;
    }

    public function getValves(): ?string
    {
        return $this->valves;
    }

    public function setValves(string $valves): static
    {
        $this->valves = $valves;

        return $this;
    }

    public function getEngineType(): ?string
    {
        return $this->engine_type;
    }

    public function setEngineType(string $engine_type): static
    {
        $this->engine_type = $engine_type;

        return $this;
    }

    public function getEngineCodes(): ?string
    {
        return $this->engine_codes;
    }

    public function setEngineCodes(string $engine_codes): static
    {
        $this->engine_codes = $engine_codes;

        return $this;
    }

    public function getKba(): ?string
    {
        return $this->kba;
    }

    public function setKba(string $kba): static
    {
        $this->kba = $kba;

        return $this;
    }

    public function getTextValue(): ?string
    {
        return $this->text_value;
    }

    public function setTextValue(string $text_value): static
    {
        $this->text_value = $text_value;

        return $this;
    }
}
