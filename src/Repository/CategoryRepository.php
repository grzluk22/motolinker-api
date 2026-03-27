<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function save(Category $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Category $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Category[] Returns an array of Category objects
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

//    public function findOneBySomeField($value): ?Category
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
    /**
     * UĹĽyte do pobierania wszystkich zagnieĹĽdĹĽonych kategorii
     *
     * @param array $parentIds
     * @return array
     */
    public function getDescendantIds(array $parentIds): array
    {
        $allIds = $parentIds;
        $children = $this->findBy(['id_parent' => $parentIds]);

        if (empty($children)) {
            return $allIds;
        }

        $childIds = array_map(fn($category) => $category->getId(), $children);
        $grandChildIds = $this->getDescendantIds($childIds);

        return array_unique(array_merge($allIds, $grandChildIds));
    }

    public function recalculateProductsCount(): void
    {
        $categories = $this->findAll();
        foreach ($categories as $category) {
            $category->setProductsCount($this->getEntityManager()->getRepository(Article::class)->count(['id_category' => $category->getId()]));
            $this->save($category, true);
        }
    }
}
