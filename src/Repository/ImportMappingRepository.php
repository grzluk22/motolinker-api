<?php

namespace App\Repository;

use App\Entity\ImportMapping;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImportMapping>
 *
 * @method ImportMapping|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImportMapping|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImportMapping[]    findAll()
 * @method ImportMapping[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImportMappingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportMapping::class);
    }

//    /**
//     * @return ImportMapping[] Returns an array of ImportMapping objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('i.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ImportMapping
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
