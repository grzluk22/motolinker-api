<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251116135700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table article_ean to support multiple EAN codes per article';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE article_ean (id INT AUTO_INCREMENT NOT NULL, id_article INT NOT NULL, ean13 VARCHAR(13) NOT NULL, UNIQUE INDEX UNIQ_ARTICLE_EAN_PER_ARTICLE (id_article, ean13), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE INDEX IDX_ARTICLE_EAN_ID_ARTICLE ON article_ean (id_article)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE article_ean');
    }
}
