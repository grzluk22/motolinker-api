<?php

namespace App\Repository;

use App\Entity\ArticleCriterionValueDescriptionLanguage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleCriterionValueDescriptionLanguage>
 *
 * @method ArticleCriterionValueDescriptionLanguage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ArticleCriterionValueDescriptionLanguage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ArticleCriterionValueDescriptionLanguage[]    findAll()
 * @method ArticleCriterionValueDescriptionLanguage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleCriterionValueDescriptionLanguageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleCriterionValueDescriptionLanguage::class);
    }

    public function save(ArticleCriterionValueDescriptionLanguage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ArticleCriterionValueDescriptionLanguage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return ArticleCriterionValueDescriptionLanguage[] Returns an array of ArticleCriterionValueDescriptionLanguage objects
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

//    public function findOneBySomeField($value): ?ArticleCriterionValueDescriptionLanguage
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
