<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;

/**
 * @extends ServiceEntityRepository<Article>
 *
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function save(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByCode($code): ?Article
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

//    /**
//     * @return Article[] Returns an array of Article objects
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

//    public function findOneBySomeField($value): ?Article
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
    public function findByExtended(mixed $criteria, mixed $orderBy, mixed $limit, mixed $offset)
    {
        $extendedCriteria = ['priceMin', 'priceMax', 'quantity', 'quantitySearchMode', 'searchLike'];

        $priceMin = $criteria['priceMin'] ?? false;
        $priceMax = $criteria['priceMax'] ?? false;
        $quantity = $criteria['quantity'] ?? false;
        $quantitySearchMode = $criteria['quantitySearchMode'] ?? false;
        $searchLike = $criteria['searchLike'] ?? false;
        $qb = $this->createQueryBuilder('a');
        foreach ($criteria as $key => $value) {
            if (in_array($key, $extendedCriteria)) continue;
            if(!$searchLike) {
                $qb->andWhere('a.'.$key.' = :'.$key);
                $qb->setParameter($key, $value);
            }else{
                $qb->andWhere($qb->expr()->like('a.'.$key, ':value'.$key))
                    ->setParameter('value' . $key, '%' . $value . '%');
            }
        }

        foreach ($extendedCriteria as $key) {
            if(!isset($criteria[$key])) continue;
            switch ($key) {
                case 'priceMin': {
                    //priceMin
                    $qb->andWhere('a.price >= :priceMin');
                    $qb->setParameter('priceMin', $priceMin);
                }
                    break;

                case 'priceMax': {
                    //priceMax
                    $qb->andWhere('a.price <= :priceMax');
                    $qb->setParameter('priceMax', $priceMax);
                }
                    break;

                case 'quantity': {
                    //quanatiy
                    switch ($quantitySearchMode) {
                        case '=': {
                            //equals
                            $qb->andWhere('a.quantity = :quantity');
                            $qb->setParameter('quantity', $quantity);
                        }
                            break;
                        case '>': {
                            //more than
                            $qb->andWhere('a.quantity > :quantity');
                            $qb->setParameter('quantity', $quantity);
                        }
                            break;
                        case '<': {
                            //less than
                            $qb->andWhere('a.quantity < :quantity');
                            $qb->setParameter('quantity', $quantity);
                        }
                            break;
                        default: {

                        }
                    }
                }
                    break;
                default: {

                }
            }
        }

        foreach ($orderBy as $key => $value) {
            $qb->addOrderBy('a.'.$key, $value);
        }
        $qb->setFirstResult($offset)
            ->setMaxResults($limit);
        return $qb->getQuery()->getResult();
    }
}
