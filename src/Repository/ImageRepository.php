<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Image>
 *
 * @method Image|null find($id, $lockMode = null, $lockVersion = null)
 * @method Image|null findOneBy(array $criteria, array $orderBy = null)
 * @method Image[]    findAll()
 * @method Image[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    /**
     * Find all images for article ordered by position
     *
     * @return Image[]
     */
    public function findByArticleOrdered(int $articleId): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.article', 'a')
            ->where('a.id = :articleId')
            ->setParameter('articleId', $articleId)
            ->orderBy('i.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get maximum position for article
     */
    public function getMaxPositionForArticle(int $articleId): int
    {
        $result = $this->createQueryBuilder('i')
            ->innerJoin('i.article', 'a')
            ->where('a.id = :articleId')
            ->setParameter('articleId', $articleId)
            ->select('MAX(i.position) as maxPosition')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Find main image for article
     */
    public function findMainImageForArticle(int $articleId): ?Image
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.article', 'a')
            ->where('a.id = :articleId')
            ->andWhere('i.is_main = :isMain')
            ->setParameter('articleId', $articleId)
            ->setParameter('isMain', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Update positions for multiple images
     *
     * @param array $imagePositions Array of ['id' => int, 'position' => int]
     */
    public function updatePositions(array $imagePositions): void
    {
        $entityManager = $this->getEntityManager();

        foreach ($imagePositions as $imageData) {
            if (!isset($imageData['id']) || !isset($imageData['position'])) {
                continue;
            }

            $image = $this->find($imageData['id']);
            if ($image) {
                $image->setPosition($imageData['position']);
                $entityManager->persist($image);
            }
        }

        $entityManager->flush();
    }
}
