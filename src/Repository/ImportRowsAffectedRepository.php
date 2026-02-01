<?php

namespace App\Repository;

use App\Entity\ImportRowsAffected;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImportRowsAffected>
 *
 * @method ImportRowsAffected|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImportRowsAffected|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImportRowsAffected[]    findAll()
 * @method ImportRowsAffected[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImportRowsAffectedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportRowsAffected::class);
    }

    /**
     * @return ImportRowsAffected[]
     */
    public function findByJobId(int $jobId): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.jobId = :val')
            ->setParameter('val', $jobId)
            ->orderBy('i.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
