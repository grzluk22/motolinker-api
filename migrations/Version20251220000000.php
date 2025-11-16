<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251220000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new fields to image table and create foreign key relationship with article';
    }

    public function up(Schema $schema): void
    {
        // Add new columns to image table
        $this->addSql('ALTER TABLE image ADD filename VARCHAR(255) NOT NULL AFTER id');
        $this->addSql('ALTER TABLE image ADD original_filename VARCHAR(255) NOT NULL AFTER filename');
        $this->addSql('ALTER TABLE image ADD file_size INT NOT NULL AFTER original_filename');
        $this->addSql('ALTER TABLE image ADD mime_type VARCHAR(100) NOT NULL AFTER file_size');
        $this->addSql('ALTER TABLE image ADD width INT NOT NULL AFTER mime_type');
        $this->addSql('ALTER TABLE image ADD height INT NOT NULL AFTER width');
        $this->addSql('ALTER TABLE image ADD is_main TINYINT(1) DEFAULT 0 NOT NULL AFTER height');
        $this->addSql('ALTER TABLE image ADD created_at DATETIME NOT NULL AFTER is_main');
        
        // Create foreign key constraint
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F6E62C6C6 FOREIGN KEY (id_article) REFERENCES article (id) ON DELETE CASCADE');
        
        // Add indexes
        $this->addSql('CREATE INDEX IDX_C53D045F6E62C6C6 ON image (id_article)');
        $this->addSql('CREATE INDEX IDX_IMAGE_POSITION ON image (id_article, position)');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key constraint
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045F6E62C6C6');
        
        // Drop indexes
        $this->addSql('DROP INDEX IDX_C53D045F6E62C6C6 ON image');
        $this->addSql('DROP INDEX IDX_IMAGE_POSITION ON image');
        
        // Remove new columns
        $this->addSql('ALTER TABLE image DROP filename');
        $this->addSql('ALTER TABLE image DROP original_filename');
        $this->addSql('ALTER TABLE image DROP file_size');
        $this->addSql('ALTER TABLE image DROP mime_type');
        $this->addSql('ALTER TABLE image DROP width');
        $this->addSql('ALTER TABLE image DROP height');
        $this->addSql('ALTER TABLE image DROP is_main');
        $this->addSql('ALTER TABLE image DROP created_at');
    }
}

