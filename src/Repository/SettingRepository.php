<?php

namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    public function getSettingsAsArray(): array
    {
        $settings = $this->findAll();
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->getSettingKey()] = $setting->getSettingValue();
        }
        return $result;
    }

    public function getSetting(string $key): ?string
    {
        $setting = $this->findOneBy(['settingKey' => $key]);
        return $setting ? $setting->getSettingValue() : null;
    }

    public function updateSetting(string $key, ?string $value): void
    {
        $setting = $this->findOneBy(['settingKey' => $key]);
        if (!$setting) {
            $setting = new Setting();
            $setting->setSettingKey($key);
        }
        $setting->setSettingValue($value);

        $this->getEntityManager()->persist($setting);
        $this->getEntityManager()->flush();
    }
}
