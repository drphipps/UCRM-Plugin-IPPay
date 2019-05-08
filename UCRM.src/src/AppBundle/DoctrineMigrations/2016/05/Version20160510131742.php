<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160510131742 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('UPDATE client SET deleted_at = deleted_from');
        $this->addSql('ALTER TABLE client DROP deleted');
        $this->addSql('ALTER TABLE client DROP deleted_from');
        $this->addSql('ALTER TABLE organization ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE site ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client ADD deleted BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE client ADD deleted_from DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE client DROP deleted_at');
        $this->addSql('ALTER TABLE site DROP deleted_at');
        $this->addSql('ALTER TABLE service DROP deleted_at');
        $this->addSql('ALTER TABLE organization DROP deleted_at');
    }
}
