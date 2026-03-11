<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260220225052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_dashboard_settings (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, widget_id VARCHAR(255) NOT NULL, is_visible TINYINT(1) NOT NULL, grid_x INT NOT NULL, grid_y INT NOT NULL, grid_cols INT NOT NULL, grid_rows INT NOT NULL, INDEX IDX_C6FFE99A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_dashboard_settings ADD CONSTRAINT FK_C6FFE99A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_dashboard_settings DROP FOREIGN KEY FK_C6FFE99A76ED395');
        $this->addSql('DROP TABLE user_dashboard_settings');
    }
}
