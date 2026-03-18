<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260131175849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_job ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE import_job ADD CONSTRAINT FK_6FB54078A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6FB54078A76ED395 ON import_job (user_id)');
        $this->addSql('ALTER TABLE import_rows_affected CHANGE table_name `table` VARCHAR(255) NOT NULL, CHANGE row_id rowID INT NOT NULL, CHANGE created_at `date` DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX UNIQ_E545A0C55FA1E697 ON settings');
        $this->addSql('ALTER TABLE settings ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE settings ADD CONSTRAINT FK_E545A0C5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_E545A0C5A76ED395 ON settings (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_job DROP FOREIGN KEY FK_6FB54078A76ED395');
        $this->addSql('DROP INDEX IDX_6FB54078A76ED395 ON import_job');
        $this->addSql('ALTER TABLE import_job DROP user_id');
        $this->addSql('ALTER TABLE import_rows_affected CHANGE `table` table_name VARCHAR(255) NOT NULL, CHANGE rowID row_id INT NOT NULL, CHANGE date created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE settings DROP FOREIGN KEY FK_E545A0C5A76ED395');
        $this->addSql('DROP INDEX IDX_E545A0C5A76ED395 ON settings');
        $this->addSql('ALTER TABLE settings DROP user_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E545A0C55FA1E697 ON settings (setting_key)');
    }
}
