<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230914051347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE car (id INT AUTO_INCREMENT NOT NULL, manufacturer VARCHAR(255) NOT NULL, model VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, model_from VARCHAR(255) NOT NULL, model_to VARCHAR(255) NOT NULL, body_type VARCHAR(255) NOT NULL, drive_type VARCHAR(255) NOT NULL, displacement_liters VARCHAR(255) NOT NULL, displacement_cmm VARCHAR(255) NOT NULL, fuel_type VARCHAR(255) NOT NULL, kw VARCHAR(255) NOT NULL, hp VARCHAR(255) NOT NULL, cylinders INT NOT NULL, valves VARCHAR(255) NOT NULL, engine_type VARCHAR(255) NOT NULL, engine_codes VARCHAR(255) NOT NULL, kba VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE car');
    }
}
