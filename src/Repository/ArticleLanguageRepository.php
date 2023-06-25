<?php

namespace App\Repository;

use App\Entity\ArticleLanguage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleLanguage>
 *
 * @method ArticleLanguage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ArticleLanguage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ArticleLanguage[]    findAll()
 * @method ArticleLanguage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleLanguageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleLanguage::class);
    }

    public function save(ArticleLanguage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ArticleLanguage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return ArticleLanguage[] Returns an array of ArticleLanguage objects
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

//    public function findOneBySomeField($value): ?ArticleLanguage
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findByArticleId($idArticle): ?array
    {
        return $this->createQueryBuilder('al')
            ->andWhere('al.id_article = :idArticle')
            ->setParameter('idArticle', $idArticle)
            ->getQuery()
            ->getResult()
        ;
    }
}
