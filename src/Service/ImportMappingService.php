<?php

namespace App\Service;

use App\Entity\ImportMapping;
use App\Repository\ImportMappingRepository;
use Doctrine\ORM\EntityManagerInterface;

class ImportMappingService
{
    public function __construct(
        private ImportMappingRepository $importMappingRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return ImportMapping[]
     */
    public function getAllMappings(): array
    {
        return $this->importMappingRepository->findAll();
    }

    public function getMappingByName(string $name): ?ImportMapping
    {
        return $this->importMappingRepository->findOneBy(['name' => $name]);
    }

    public function deleteMapping(string $name): void
    {
        $mapping = $this->getMappingByName($name);
        if ($mapping) {
            $this->entityManager->remove($mapping);
            $this->entityManager->flush();
        }
    }

    public function saveMapping(string $name, array $mappingData): ImportMapping
    {
        $mapping = $this->importMappingRepository->findOneBy(['name' => $name]);

        if (!$mapping) {
            $mapping = new ImportMapping();
            $mapping->setName($name);
        }

        $mapping->setMapping($mappingData);

        $this->entityManager->persist($mapping);
        $this->entityManager->flush();

        return $mapping;
    }
}
