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
     * Find main images for multiple articles
     * Returns array keyed by article ID
     *
     * @param int[] $articleIds
     * @return Image[] Array keyed by article ID
     */
    public function findMainImagesForArticles(array $articleIds): array
    {
        if (empty($articleIds)) {
            return [];
        }

        $result = [];

        // Pobierz główne zdjęcia (is_main = true) używając bezpośredniego dostępu do id_article przez DQL
        $mainImagesData = $this->createQueryBuilder('i')
            ->select('i.id as image_id, a.id as article_id')
            ->innerJoin('i.article', 'a')
            ->where('a.id IN (:articleIds)')
            ->andWhere('i.is_main = :isMain')
            ->setParameter('articleIds', $articleIds)
            ->setParameter('isMain', true)
            ->getQuery()
            ->getResult();

        // Pobierz pełne obiekty Image dla znalezionych ID
        if (!empty($mainImagesData)) {
            $imageIds = array_map(fn($row) => $row['image_id'], $mainImagesData);
            $mainImages = $this->createQueryBuilder('i')
                ->innerJoin('i.article', 'a')
                ->addSelect('a')
                ->where('i.id IN (:imageIds)')
                ->setParameter('imageIds', $imageIds)
                ->getQuery()
                ->getResult();

            // Mapuj obrazy do article_id
            $imageIdToArticleId = [];
            foreach ($mainImagesData as $row) {
                $imageIdToArticleId[$row['image_id']] = $row['article_id'];
            }

            foreach ($mainImages as $image) {
                $imageId = $image->getId();
                if (isset($imageIdToArticleId[$imageId])) {
                    $articleId = $imageIdToArticleId[$imageId];
                    $result[$articleId] = $image;
                }
            }
        }

        // Dla artykułów bez głównego zdjęcia, pobierz pierwsze zdjęcie (pozycja ASC)
        $articlesWithoutMain = array_values(array_diff($articleIds, array_keys($result)));
        if (!empty($articlesWithoutMain)) {
            $firstImagesData = $this->createQueryBuilder('i')
                ->select('i.id as image_id, a.id as article_id, i.position')
                ->innerJoin('i.article', 'a')
                ->where('a.id IN (:articleIds)')
                ->setParameter('articleIds', $articlesWithoutMain)
                ->orderBy('i.position', 'ASC')
                ->getQuery()
                ->getResult();

            if (!empty($firstImagesData)) {
                // Grupuj po artykule i weź pierwsze dla każdego
                $firstByArticleId = [];
                foreach ($firstImagesData as $row) {
                    $articleId = $row['article_id'];
                    if (!isset($result[$articleId]) && !isset($firstByArticleId[$articleId])) {
                        $firstByArticleId[$articleId] = $row['image_id'];
                    }
                }

                if (!empty($firstByArticleId)) {
                    $firstImageIds = array_values($firstByArticleId);
                    $firstImages = $this->createQueryBuilder('i')
                        ->innerJoin('i.article', 'a')
                        ->addSelect('a')
                        ->where('i.id IN (:imageIds)')
                        ->setParameter('imageIds', $firstImageIds)
                        ->getQuery()
                        ->getResult();

                    // Mapuj obrazy do article_id
                    $imageIdToArticleId = [];
                    foreach ($firstImagesData as $row) {
                        if (in_array($row['image_id'], $firstImageIds)) {
                            $imageIdToArticleId[$row['image_id']] = $row['article_id'];
                        }
                    }

                    foreach ($firstImages as $image) {
                        $imageId = $image->getId();
                        if (isset($imageIdToArticleId[$imageId])) {
                            $articleId = $imageIdToArticleId[$imageId];
                            if (!isset($result[$articleId])) {
                                $result[$articleId] = $image;
                            }
                        }
                    }
                }
            }
        }

        return $result;
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
