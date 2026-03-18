<?php

namespace App\Repository;

use App\Entity\ArticleEan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleEan>
 *
 * @method ArticleEan|null find($id, $lockMode = null, $lockVersion = null)
 * @method ArticleEan|null findOneBy(array $criteria, array $orderBy = null)
 * @method ArticleEan[]    findAll()
 * @method ArticleEan[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleEanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleEan::class);
    }

    /**
     * @return ArticleEan[]
     */
    public function findByArticleId(int $id_article): array
    {
        return $this->createQueryBuilder('ae')
            ->andWhere('ae.id_article = :id')
            ->setParameter('id', $id_article)
            ->getQuery()
            ->getResult();
    }

    public function save(ArticleEan $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ArticleEan $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
