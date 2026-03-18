<?php

namespace App\Repository;

use App\Entity\ImportJob;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImportJob>
 */
class ImportJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportJob::class);
    }

    /**
     * @param array $criteria
     * @param array $orderBy
     * @param int $limit
     * @param int $offset
     * @return ImportJob[]
     */
    public function findWithFilters(array $criteria = [], array $orderBy = [], int $limit = 100, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('j');

        foreach ($criteria as $field => $value) {
            if ($value !== null && $value !== '') {
                // To avoid SQL injection on field names, we should make sure $field is a valid property name
                if (property_exists(ImportJob::class, $field)) {
                    // For string values, you might consider LIKE, but for general compatibility, equality is used.
                    // If you need more complex mappings, you can expand this section.
                    $qb->andWhere(sprintf('j.%s = :%s', $field, $field))
                       ->setParameter($field, $value);
                }
            }
        }

        foreach ($orderBy as $field => $direction) {
            if (property_exists(ImportJob::class, $field)) {
                $qb->addOrderBy(sprintf('j.%s', $field), strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC');
            }
        }

        $qb->setMaxResults($limit)
           ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all import jobs ordered by creation date (newest first)
     * 
     * @param string|null $status Optional status filter
     * @param int $limit Maximum number of results (default: 100)
     * @return ImportJob[]
     */
    public function findAllOrderedByDate(?string $status = null, int $limit = 100): array
    {
        $criteria = [];
        if ($status !== null) {
            $criteria['status'] = $status;
        }

        return $this->findWithFilters($criteria, ['createdAt' => 'DESC'], $limit);
    }
}
