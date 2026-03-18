<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    public function findByExtended(mixed $criteria, mixed $orderBy, mixed $limit, mixed $offset)
    {
        $this->expandCategoryIds($criteria);
        $extendedCriteria = ['priceMin', 'priceMax', 'quantity', 'quantitySearchMode', 'searchLike', 'image'];

        $priceMin = $criteria['priceMin'] ?? false;
        $priceMax = $criteria['priceMax'] ?? false;
        $quantity = $criteria['quantity'] ?? false;
        $quantitySearchMode = $criteria['quantitySearchMode'] ?? false;
        $searchLike = $criteria['searchLike'] ?? false;
        $hasImage = $criteria['image'] ?? null;
        $qb = $this->createQueryBuilder('a');
        foreach ($criteria as $key => $value) {
            if (in_array($key, $extendedCriteria)) continue;
            if(!$value or $value == "" or $value == -1) continue;
            if ($key === 'ean13') {
                // Zapytanie ma uwzględniać ean także w tabeli article_ean
                if(!$searchLike) {
                    $qb->leftJoin('App\\Entity\\ArticleEan', 'ae', 'WITH', 'ae.id_article = a.id')
                        ->andWhere('(a.ean13 = :ean13 OR ae.ean13 = :ean13)')
                        ->setParameter('ean13', $value);
                } else {
                    $qb->leftJoin('App\\Entity\\ArticleEan', 'ae', 'WITH', 'ae.id_article = a.id')
                        ->andWhere('(a.ean13 LIKE :valueean13 OR ae.ean13 LIKE :valueean13)')
                        ->setParameter('valueean13', '%' . $value . '%');
                }
                continue;
            }
            if(!$searchLike) {
                if (is_array($value)) {
                    $qb->andWhere('a.'.$key.' IN (:'.$key.')');
                    $qb->setParameter($key, $value);
                } else {
                    $qb->andWhere('a.'.$key.' = :'.$key);
                    $qb->setParameter($key, $value);
                }
            }else{
                $qb->andWhere($qb->expr()->like('a.'.$key, ':value'.$key))
                    ->setParameter('value' . $key, '%' . $value . '%');
            }
        }

        foreach ($extendedCriteria as $key => $value) {
            if(!isset($criteria[$value])) {
                continue;
            };
            
            // Dla parametru 'image' sprawdzamy czy jest ustawiony (true/false), nie pomijamy false
            if ($value === 'image') {
                // Przetwarzamy image osobno, nie pomijamy false
            } elseif (!$criteria[$value] or $criteria[$value] == "" or $criteria[$value] == -1) {
                continue;
            }
            
            switch ($value) {
                case 'priceMin': {
                    $qb->andWhere('a.price >= :priceMin');
                    $qb->setParameter('priceMin', $priceMin);
                }
                    break;

                case 'priceMax': {
                    $qb->andWhere('a.price <= :priceMax');
                    $qb->setParameter('priceMax', $priceMax);
                }
                    break;

                case 'quantity': {
                    switch ($quantitySearchMode) {
                        case '=': {
                            $qb->andWhere('a.quantity = :quantity');
                            $qb->setParameter('quantity', $quantity);
                        }
                            break;
                        case '>': {
                            $qb->andWhere('a.quantity > :quantity');
                            $qb->setParameter('quantity', $quantity);
                        }
                            break;
                        case '<': {
                            $qb->andWhere('a.quantity < :quantity');
                            $qb->setParameter('quantity', $quantity);
                        }
                            break;
                        default: {
                        }
                    }
                }
                    break;

                case 'image': {
                    // Filtruj artykuły pod kątem posiadania zdjęć
                    if ($hasImage === true) {
                        // Tylko artykuły ze zdjęciami - użyj relacji images z encji Article
                        $qb->innerJoin('a.images', 'img');
                        $qb->groupBy('a.id');
                    } elseif ($hasImage === false) {
                        // Tylko artykuły bez zdjęć
                        $qb->leftJoin('a.images', 'img')
                            ->andWhere('img.id IS NULL');
                    }
                    // Jeśli hasImage jest null lub nie ustawione, nie filtruj
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

    public function countByExtended(mixed $criteria): int
    {
        $this->expandCategoryIds($criteria);
        $extendedCriteria = ['priceMin', 'priceMax', 'quantity', 'quantitySearchMode', 'searchLike', 'image'];

        $priceMin = $criteria['priceMin'] ?? false;
        $priceMax = $criteria['priceMax'] ?? false;
        $quantity = $criteria['quantity'] ?? false;
        $quantitySearchMode = $criteria['quantitySearchMode'] ?? false;
        $searchLike = $criteria['searchLike'] ?? false;
        $hasImage = $criteria['image'] ?? null;
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(DISTINCT a.id)');
        
        foreach ($criteria as $key => $value) {
            if (in_array($key, $extendedCriteria)) continue;
            if(!$value or $value == "" or $value == -1) continue;
            if ($key === 'ean13') {
                // Zapytanie ma uwzględniać ean także w tabeli article_ean
                if(!$searchLike) {
                    $qb->leftJoin('App\\Entity\\ArticleEan', 'ae', 'WITH', 'ae.id_article = a.id')
                        ->andWhere('(a.ean13 = :ean13 OR ae.ean13 = :ean13)')
                        ->setParameter('ean13', $value);
                } else {
                    $qb->leftJoin('App\\Entity\\ArticleEan', 'ae', 'WITH', 'ae.id_article = a.id')
                        ->andWhere('(a.ean13 LIKE :valueean13 OR ae.ean13 LIKE :valueean13)')
                        ->setParameter('valueean13', '%' . $value . '%');
                }
                continue;
            }
            if(!$searchLike) {
                if (is_array($value)) {
                    $qb->andWhere('a.'.$key.' IN (:'.$key.')');
                    $qb->setParameter($key, $value);
                } else {
                    $qb->andWhere('a.'.$key.' = :'.$key);
                    $qb->setParameter($key, $value);
                }
            }else{
                $qb->andWhere($qb->expr()->like('a.'.$key, ':value'.$key))
                    ->setParameter('value' . $key, '%' . $value . '%');
            }
        }

        foreach ($extendedCriteria as $key => $value) {
            if(!isset($criteria[$value])) {
                continue;
            };
            
            // Dla parametru 'image' sprawdzamy czy jest ustawiony (true/false), nie pomijamy false
            if ($value === 'image') {
                // Przetwarzamy image osobno, nie pomijamy false
            } elseif (!$criteria[$value] or $criteria[$value] == "" or $criteria[$value] == -1) {
                continue;
            }
            
            switch ($value) {
                case 'priceMin': {
                    $qb->andWhere('a.price >= :priceMin');
                    $qb->setParameter('priceMin', $priceMin);
                }
                    break;

                case 'priceMax': {
                    $qb->andWhere('a.price <= :priceMax');
                    $qb->setParameter('priceMax', $priceMax);
                }
                    break;

                case 'quantity': {
                    switch ($quantitySearchMode) {
                        case '=': {
                            $qb->andWhere('a.quantity = :quantity');
                            $qb->setParameter('quantity', $quantity);
                        }
                            break;
                        case '>': {
                            $qb->andWhere('a.quantity > :quantity');
                            $qb->setParameter('quantity', $quantity);
                        }
                            break;
                        case '<': {
                            $qb->andWhere('a.quantity < :quantity');
                            $qb->setParameter('quantity', $quantity);
                        }
                            break;
                        default: {
                        }
                    }
                }
                    break;

                case 'image': {
                    // Filtruj artykuły pod kątem posiadania zdjęć
                    if ($hasImage === true) {
                        // Tylko artykuły ze zdjęciami - użyj relacji images z encji Article
                        $qb->innerJoin('a.images', 'img');
                    } elseif ($hasImage === false) {
                        // Tylko artykuły bez zdjęć
                        $qb->leftJoin('a.images', 'img')
                            ->andWhere('img.id IS NULL');
                    }
                    // Jeśli hasImage jest null lub nie ustawione, nie filtruj
                }
                    break;

                default: {
                }
            }
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Zwraca tylko tablicę ciągów znaków reprezentującą kody wszystkich artykułów
     */
    public function findAllArticleCodes(): array
    {
        $result = $this->createQueryBuilder('a')
            ->select('a.code')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'code');
    }

    private function expandCategoryIds(mixed &$criteria): void
    {
        if (isset($criteria['id_category']) && !empty($criteria['id_category']) && $criteria['id_category'] != -1) {
            $categoryId = $criteria['id_category'];
            $parentIds = is_array($categoryId) ? $categoryId : [$categoryId];

            /** @var \App\Repository\CategoryRepository $categoryRepository */
            $categoryRepository = $this->getEntityManager()->getRepository(Category::class);
            $expandedIds = $categoryRepository->getDescendantIds($parentIds);

            $criteria['id_category'] = $expandedIds;
        }
    }
}
