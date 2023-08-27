<?php

namespace App\Repository;

use App\Entity\CriterionLanguage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CriterionLanguage>
 *
 * @method CriterionLanguage|null find($id, $lockMode = null, $lockVersion = null)
 * @method CriterionLanguage|null findOneBy(array $criteria, array $orderBy = null)
 * @method CriterionLanguage[]    findAll()
 * @method CriterionLanguage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CriterionLanguageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CriterionLanguage::class);
    }

    public function save(CriterionLanguage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CriterionLanguage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return CriterionLanguage[] Returns an array of CriterionLanguage objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CriterionLanguage
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
