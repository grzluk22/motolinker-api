<?php

namespace App\Repository;

use App\Entity\UserDashboardSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserDashboardSetting>
 *
 * @method UserDashboardSetting|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserDashboardSetting|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserDashboardSetting[]    findAll()
 * @method UserDashboardSetting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserDashboardSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDashboardSetting::class);
    }

    public function save(UserDashboardSetting $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserDashboardSetting $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
