<?php

namespace App\Repository;

use App\Entity\AvailableRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AvailableRole>
 *
 * @method AvailableRole|null find($id, $lockMode = null, $lockVersion = null)
 * @method AvailableRole|null findOneBy(array $criteria, array $orderBy = null)
 * @method AvailableRole[]    findAll()
 * @method AvailableRole[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AvailableRoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvailableRole::class);
    }

    public function save(AvailableRole $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AvailableRole $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
