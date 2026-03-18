<?php

namespace App\Service;

use App\Entity\ImportMapping;
use App\Repository\ImportMappingRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

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

    public function getMappingById(int $id): ?ImportMapping
    {
        return $this->importMappingRepository->find($id);
    }

    public function deleteMapping(int $id): void
    {
        $mapping = $this->getMappingById($id);
        if ($mapping) {
            $this->entityManager->remove($mapping);
            $this->entityManager->flush();
        }
    }

    public function bulkDeleteMapping(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $mappings = $this->importMappingRepository->findBy(['id' => $ids]);
        foreach ($mappings as $mapping) {
            $this->entityManager->remove($mapping);
        }

        $this->entityManager->flush();
    }

    public function createMapping(array $data): ImportMapping
    {
        if (isset($data['name'])) {
            $existing = $this->importMappingRepository->findOneBy(['name' => $data['name']]);
            if ($existing) {
                throw new InvalidArgumentException('Mapping with this name already exists');
            }
        } else {
            throw new InvalidArgumentException('Mapping name is required');
        }

        $mapping = new ImportMapping();
        return $this->updateMappingFromData($mapping, $data);
    }

    public function updateMapping(int $id, array $data): ImportMapping
    {
        $mapping = $this->getMappingById($id);
        if (!$mapping) {
            throw new InvalidArgumentException('Mapping not found');
        }

        if (isset($data['name']) && strcasecmp($mapping->getName(), $data['name']) !== 0) {
            $existing = $this->importMappingRepository->findOneBy(['name' => $data['name']]);
            if ($existing && $existing->getId() !== $id) {
                throw new InvalidArgumentException('Mapping with this name already exists');
            }
        }

        return $this->updateMappingFromData($mapping, $data);
    }

    private function updateMappingFromData(ImportMapping $mapping, array $data): ImportMapping
    {
        if (isset($data['name'])) {
            $mapping->setName($data['name']);
        }
        
        $mapping->setIsDefault($data['is_default'] ?? false);
        $mapping->setMappingData($data['mapping_data'] ?? []);
        $mapping->setUniquenessField($data['uniqueness_field'] ?? 'article_code');
        $mapping->setOnDuplicateAction($data['on_duplicate_action'] ?? 'skip');
        $mapping->setFieldsToUpdate($data['fields_to_update'] ?? null);

        if ($mapping->isDefault()) {
            $others = $this->importMappingRepository->findBy(['isDefault' => true]);
            foreach ($others as $other) {
                if ($other->getId() !== $mapping->getId()) {
                    $other->setIsDefault(false);
                }
            }
        }

        $this->entityManager->persist($mapping);
        $this->entityManager->flush();

        return $mapping;
    }
}
