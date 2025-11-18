<?php

namespace App\Repository;

use App\Entity\Car;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Car>
 *
 * @method Car|null find($id, $lockMode = null, $lockVersion = null)
 * @method Car|null findOneBy(array $criteria, array $orderBy = null)
 * @method Car[]    findAll()
 * @method Car[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Car::class);
    }

    public function save(Car $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Car $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Car[] Returns an array of Car objects
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

//    public function findOneBySomeField($value): ?Car
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function search(string $text_value)
    {
        /* Metoda wyszukuje samochód na podstawie wprowadzonego tekstu */
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder->andWhere('c.text_value LIKE :text_value')
            ->setParameter('text_value', '%'.$text_value.'%');

        return $queryBuilder
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function findByExtended(mixed $criteria, mixed $orderBy, mixed $limit, mixed $offset)
    {
        $extendedCriteria = ['searchLike'];
        $searchLike = $criteria['searchLike'] ?? false;
        
        $qb = $this->createQueryBuilder('c');
        
        foreach ($criteria as $key => $value) {
            if (in_array($key, $extendedCriteria)) continue;
            if(!$value or $value == "" or $value == -1) continue;
            
            if(!$searchLike) {
                $qb->andWhere('c.'.$key.' = :'.$key);
                $qb->setParameter($key, $value);
            } else {
                $qb->andWhere($qb->expr()->like('c.'.$key, ':value'.$key))
                    ->setParameter('value' . $key, '%' . $value . '%');
            }
        }

        foreach ($orderBy as $key => $value) {
            $qb->addOrderBy('c.'.$key, $value);
        }
        
        $qb->setFirstResult($offset)
            ->setMaxResults($limit);
            
        return $qb->getQuery()->getResult();
    }

    public function countByExtended(mixed $criteria): int
    {
        $extendedCriteria = ['searchLike'];
        $searchLike = $criteria['searchLike'] ?? false;
        
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.id)');
        
        foreach ($criteria as $key => $value) {
            if (in_array($key, $extendedCriteria)) continue;
            if(!$value or $value == "" or $value == -1) continue;
            
            if(!$searchLike) {
                $qb->andWhere('c.'.$key.' = :'.$key);
                $qb->setParameter($key, $value);
            } else {
                $qb->andWhere($qb->expr()->like('c.'.$key, ':value'.$key))
                    ->setParameter('value' . $key, '%' . $value . '%');
            }
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
