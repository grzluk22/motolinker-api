<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260131203202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_user_group (user_id INT NOT NULL, user_group_id INT NOT NULL, INDEX IDX_28657971A76ED395 (user_id), INDEX IDX_286579711ED93D47 (user_group_id), PRIMARY KEY(user_id, user_group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_group (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, roles JSON NOT NULL, is_default TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8F02BF9D5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_user_group ADD CONSTRAINT FK_28657971A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_user_group ADD CONSTRAINT FK_286579711ED93D47 FOREIGN KEY (user_group_id) REFERENCES user_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article CHANGE ean13 ean13 VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX IDX_ARTICLE_EAN_ID_ARTICLE ON article_ean');
        $this->addSql('DROP INDEX UNIQ_ARTICLE_EAN_PER_ARTICLE ON article_ean');
        $this->addSql('DROP INDEX IDX_IMAGE_POSITION ON image');
        $this->addSql('ALTER TABLE image RENAME INDEX idx_c53d045f6e62c6c6 TO IDX_C53D045FDCA7A716');
        $this->addSql('DROP INDEX IDX_75EA56E016BA31DB ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0E3BD61CE ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_user_group DROP FOREIGN KEY FK_28657971A76ED395');
        $this->addSql('ALTER TABLE user_user_group DROP FOREIGN KEY FK_286579711ED93D47');
        $this->addSql('DROP TABLE user_user_group');
        $this->addSql('DROP TABLE user_group');
        $this->addSql('ALTER TABLE article CHANGE ean13 ean13 VARCHAR(13) NOT NULL');
        $this->addSql('CREATE INDEX IDX_ARTICLE_EAN_ID_ARTICLE ON article_ean (id_article)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ARTICLE_EAN_PER_ARTICLE ON article_ean (id_article, ean13)');
        $this->addSql('CREATE INDEX IDX_IMAGE_POSITION ON image (id_article, position)');
        $this->addSql('ALTER TABLE image RENAME INDEX idx_c53d045fdca7a716 TO IDX_C53D045F6E62C6C6');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
    }
}
