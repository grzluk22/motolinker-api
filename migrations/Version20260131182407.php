<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260131182407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        if (!$schema->getTable('import_job')->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE import_job ADD user_id INT NOT NULL');
        }
        if (!$schema->getTable('import_job')->hasForeignKey('FK_6FB54078A76ED395')) {
            $this->addSql('ALTER TABLE import_job ADD CONSTRAINT FK_6FB54078A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        }
        if (!$schema->getTable('import_job')->hasIndex('IDX_6FB54078A76ED395')) {
            $this->addSql('CREATE INDEX IDX_6FB54078A76ED395 ON import_job (user_id)');
        }
        if ($schema->hasTable('import_rows_affected')) {
            $this->addSql('ALTER TABLE import_rows_affected CHANGE table_name `table` VARCHAR(255) NOT NULL, CHANGE row_id rowID INT NOT NULL, CHANGE created_at `date` DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        } else {
            $this->addSql('CREATE TABLE import_rows_affected (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, job_id INT NOT NULL, `table` VARCHAR(255) NOT NULL, rowID INT NOT NULL, `date` DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
        if ($schema->getTable('settings')->hasIndex('UNIQ_E545A0C55FA1E697')) {
            $this->addSql('DROP INDEX UNIQ_E545A0C55FA1E697 ON settings');
        }
        if (!$schema->getTable('settings')->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE settings ADD user_id INT NOT NULL');
        }
        if (!$schema->getTable('settings')->hasForeignKey('FK_E545A0C5A76ED395')) {
            $this->addSql('ALTER TABLE settings ADD CONSTRAINT FK_E545A0C5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        }
        if (!$schema->getTable('settings')->hasIndex('IDX_E545A0C5A76ED395')) {
            $this->addSql('CREATE INDEX IDX_E545A0C5A76ED395 ON settings (user_id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        /* dont use fixed indexes and foreign keys */
        $this->addSql('ALTER TABLE import_job DROP FOREIGN KEY FK_6FB54078A76ED395');
        $this->addSql('DROP INDEX IDX_6FB54078A76ED395 ON import_job');
        $this->addSql('ALTER TABLE import_rows_affected CHANGE `table` table_name VARCHAR(255) NOT NULL, CHANGE rowID row_id INT NOT NULL, CHANGE date created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE settings DROP FOREIGN KEY FK_E545A0C5A76ED395');
        $this->addSql('DROP INDEX IDX_E545A0C5A76ED395 ON settings');
        $this->addSql('ALTER TABLE settings DROP user_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E545A0C55FA1E697 ON settings (setting_key)');
        $this->addSql('ALTER TABLE import_job DROP user_id');
    }
}
