<?php

namespace App\Repository;

use App\Entity\Setting;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Setting>
 *
 * @method Setting|null find($id, $lockMode = null, $lockVersion = null)
 * @method Setting|null findOneBy(array $criteria, array $orderBy = null)
 * @method Setting[]    findAll()
 * @method Setting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    public function getSettingsAsArray(UserInterface $user): array
    {
        $settings = $this->findBy(['user' => $user]);
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->getSettingKey()] = $setting->getSettingValue();
        }
        return $result;
    }

    public function getSetting(string $key, UserInterface $user): ?string
    {
        $setting = $this->findOneBy(['settingKey' => $key, 'user' => $user]);
        return $setting ? $setting->getSettingValue() : null;
    }

    public function updateSetting(string $key, ?string $value, UserInterface $user): void
    {
        $key = str_replace('-', '_', $key);
        $setting = $this->findOneBy(['settingKey' => $key, 'user' => $user]);
        if (!$setting) {
            $setting = new Setting();
            $setting->setSettingKey($key);
            $setting->setUser($user);
        }
        $setting->setSettingValue($value);

        $this->getEntityManager()->persist($setting);
        $this->getEntityManager()->flush();
    }

    public function deleteSetting(string $key, UserInterface $user): bool
    {
        $key = str_replace('-', '_', $key);
        $setting = $this->findOneBy(['settingKey' => $key, 'user' => $user]);
        if (!$setting) {
            return false;
        }

        $this->getEntityManager()->remove($setting);
        $this->getEntityManager()->flush();

        return true;
    }
}
