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
     * Find all import jobs ordered by creation date (newest first)
     * 
     * @param string|null $status Optional status filter
     * @param int $limit Maximum number of results (default: 100)
     * @return ImportJob[]
     */
    public function findAllOrderedByDate(?string $status = null, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('j')
            ->orderBy('j.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($status !== null) {
            $qb->where('j.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }
}
