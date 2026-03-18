<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312224855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, ean13 VARCHAR(255) DEFAULT NULL, price DOUBLE PRECISION NOT NULL, id_category INT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, UNIQUE INDEX UNIQ_23A0E6677153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE article_car (id INT AUTO_INCREMENT NOT NULL, id_article INT NOT NULL, id_car INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE article_category (id INT AUTO_INCREMENT NOT NULL, id_article INT NOT NULL, id_category INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE article_criterion (id INT AUTO_INCREMENT NOT NULL, id_article INT NOT NULL, id_criterion INT NOT NULL, value VARCHAR(255) NOT NULL, value_description VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE article_criterion_value_description_language (id INT AUTO_INCREMENT NOT NULL, id_article_criterion INT NOT NULL, id_language INT NOT NULL, value_description VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE article_ean (id INT AUTO_INCREMENT NOT NULL, id_article INT NOT NULL, ean13 VARCHAR(13) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE article_language (id INT AUTO_INCREMENT NOT NULL, id_article INT NOT NULL, id_language INT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE article_stock (id INT AUTO_INCREMENT NOT NULL, id_article INT NOT NULL, id_stock INT NOT NULL, quantity INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE available_role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_885A5FD05E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE car (id INT AUTO_INCREMENT NOT NULL, manufacturer VARCHAR(255) NOT NULL, model VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, model_from VARCHAR(255) NOT NULL, model_to VARCHAR(255) NOT NULL, body_type VARCHAR(255) NOT NULL, drive_type VARCHAR(255) NOT NULL, displacement_liters VARCHAR(255) NOT NULL, displacement_cmm VARCHAR(255) NOT NULL, fuel_type VARCHAR(255) NOT NULL, kw VARCHAR(255) NOT NULL, hp VARCHAR(255) NOT NULL, cylinders INT NOT NULL, valves VARCHAR(255) NOT NULL, engine_type VARCHAR(255) NOT NULL, engine_codes VARCHAR(255) NOT NULL, kba VARCHAR(255) NOT NULL, text_value VARCHAR(255) DEFAULT NULL, hash VARCHAR(64) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, id_parent INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category_language (id INT AUTO_INCREMENT NOT NULL, id_category INT NOT NULL, id_language INT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE criterion (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE criterion_language (id INT AUTO_INCREMENT NOT NULL, id_criterion INT NOT NULL, id_language INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE external_database (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, api_url VARCHAR(255) NOT NULL, api_key VARCHAR(255) NOT NULL, type VARCHAR(50) DEFAULT \'prestashop\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE image (id INT AUTO_INCREMENT NOT NULL, id_article INT NOT NULL, filename VARCHAR(255) NOT NULL, original_filename VARCHAR(255) NOT NULL, file_size INT NOT NULL, mime_type VARCHAR(100) NOT NULL, width INT NOT NULL, height INT NOT NULL, is_main TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, position INT NOT NULL, url VARCHAR(255) NOT NULL, INDEX IDX_C53D045FDCA7A716 (id_article), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE import_job (id INT AUTO_INCREMENT NOT NULL, external_database_id INT DEFAULT NULL, user_id INT DEFAULT NULL, status VARCHAR(255) NOT NULL, file_path VARCHAR(255) DEFAULT NULL, processed_offset BIGINT NOT NULL, processed_rows INT NOT NULL, total_rows INT DEFAULT NULL, mapping JSON DEFAULT NULL, source VARCHAR(50) NOT NULL, source_ids JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', article_identifier_field VARCHAR(255) DEFAULT NULL, import_type VARCHAR(50) DEFAULT \'articles\' NOT NULL, debug_delay INT DEFAULT NULL, error_message LONGTEXT DEFAULT NULL, INDEX IDX_6FB54078986D44AC (external_database_id), INDEX IDX_6FB54078A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE import_mapping (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, is_default TINYINT(1) DEFAULT 0 NOT NULL, mapping_data JSON NOT NULL, uniqueness_field VARCHAR(255) DEFAULT \'article_code\' NOT NULL, on_duplicate_action VARCHAR(255) DEFAULT NULL, fields_to_update JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_5AF685665E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE import_rows_affected (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, job_id INT NOT NULL, `table` VARCHAR(255) NOT NULL, rowID INT NOT NULL, `date` DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE language (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, iso_code VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reference (id INT AUTO_INCREMENT NOT NULL, id_article INT NOT NULL, type INT NOT NULL, brand VARCHAR(255) NOT NULL, number VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reference_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE refresh_tokens (id INT AUTO_INCREMENT NOT NULL, refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid DATETIME NOT NULL, UNIQUE INDEX UNIQ_9BACE7E1C74F2195 (refresh_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE settings (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, setting_key VARCHAR(255) NOT NULL, setting_value LONGTEXT DEFAULT NULL, INDEX IDX_E545A0C5A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stock (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_user_group (user_id INT NOT NULL, user_group_id INT NOT NULL, INDEX IDX_28657971A76ED395 (user_id), INDEX IDX_286579711ED93D47 (user_group_id), PRIMARY KEY(user_id, user_group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_dashboard_settings (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, widget_id VARCHAR(255) NOT NULL, is_visible TINYINT(1) NOT NULL, grid_x INT NOT NULL, grid_y INT NOT NULL, grid_cols INT NOT NULL, grid_rows INT NOT NULL, config JSON DEFAULT NULL, INDEX IDX_C6FFE99A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_group (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, roles JSON NOT NULL, is_default TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8F02BF9D5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045FDCA7A716 FOREIGN KEY (id_article) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE import_job ADD CONSTRAINT FK_6FB54078986D44AC FOREIGN KEY (external_database_id) REFERENCES external_database (id)');
        $this->addSql('ALTER TABLE import_job ADD CONSTRAINT FK_6FB54078A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE settings ADD CONSTRAINT FK_E545A0C5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_user_group ADD CONSTRAINT FK_28657971A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_user_group ADD CONSTRAINT FK_286579711ED93D47 FOREIGN KEY (user_group_id) REFERENCES user_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_dashboard_settings ADD CONSTRAINT FK_C6FFE99A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045FDCA7A716');
        $this->addSql('ALTER TABLE import_job DROP FOREIGN KEY FK_6FB54078986D44AC');
        $this->addSql('ALTER TABLE import_job DROP FOREIGN KEY FK_6FB54078A76ED395');
        $this->addSql('ALTER TABLE settings DROP FOREIGN KEY FK_E545A0C5A76ED395');
        $this->addSql('ALTER TABLE user_user_group DROP FOREIGN KEY FK_28657971A76ED395');
        $this->addSql('ALTER TABLE user_user_group DROP FOREIGN KEY FK_286579711ED93D47');
        $this->addSql('ALTER TABLE user_dashboard_settings DROP FOREIGN KEY FK_C6FFE99A76ED395');
        $this->addSql('DROP TABLE article');
        $this->addSql('DROP TABLE article_car');
        $this->addSql('DROP TABLE article_category');
        $this->addSql('DROP TABLE article_criterion');
        $this->addSql('DROP TABLE article_criterion_value_description_language');
        $this->addSql('DROP TABLE article_ean');
        $this->addSql('DROP TABLE article_language');
        $this->addSql('DROP TABLE article_stock');
        $this->addSql('DROP TABLE available_role');
        $this->addSql('DROP TABLE car');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE category_language');
        $this->addSql('DROP TABLE criterion');
        $this->addSql('DROP TABLE criterion_language');
        $this->addSql('DROP TABLE external_database');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE import_job');
        $this->addSql('DROP TABLE import_mapping');
        $this->addSql('DROP TABLE import_rows_affected');
        $this->addSql('DROP TABLE language');
        $this->addSql('DROP TABLE reference');
        $this->addSql('DROP TABLE reference_type');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE settings');
        $this->addSql('DROP TABLE stock');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_user_group');
        $this->addSql('DROP TABLE user_dashboard_settings');
        $this->addSql('DROP TABLE user_group');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
