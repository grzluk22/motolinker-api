<?php

namespace App\Repository;

use App\Entity\CriterionValueLanguage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CriterionValueLanguage>
 *
 * @method CriterionValueLanguage|null find($id, $lockMode = null, $lockVersion = null)
 * @method CriterionValueLanguage|null findOneBy(array $criteria, array $orderBy = null)
 * @method CriterionValueLanguage[]    findAll()
 * @method CriterionValueLanguage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CriterionValueLanguageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CriterionValueLanguage::class);
    }

    public function save(CriterionValueLanguage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CriterionValueLanguage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return CriterionValueLanguage[] Returns an array of CriterionValueLanguage objects
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

//    public function findOneBySomeField($value): ?CriterionValueLanguage
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
