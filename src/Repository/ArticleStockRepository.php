<?php

namespace App\Repository;

use App\Entity\ArticleStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleStock>
 *
 * @method ArticleStock|null find($id, $lockMode = null, $lockVersion = null)
 * @method ArticleStock|null findOneBy(array $criteria, array $orderBy = null)
 * @method ArticleStock[]    findAll()
 * @method ArticleStock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleStockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleStock::class);
    }

//    /**
//     * @return ArticleStock[] Returns an array of ArticleStock objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ArticleStock
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
