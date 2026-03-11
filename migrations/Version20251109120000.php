<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251109120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change article.ean13 column type from INT to VARCHAR(13)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article CHANGE ean13 ean13 VARCHAR(13) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article CHANGE ean13 ean13 INT NOT NULL');
    }
}
