<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160927071051 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO setting_category (category_id, name) VALUES (8, \'netflow\')');
        $this->addSql('ALTER TABLE device ADD netflow_enabled INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE device ADD netflow_synchronized BOOLEAN DEFAULT \'true\' NOT NULL');
        $this->addSql('ALTER TABLE device ADD netflow_log TEXT DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM setting_category WHERE category_id = 8');
        $this->addSql('ALTER TABLE device DROP netflow_enabled');
        $this->addSql('ALTER TABLE device DROP netflow_synchronized');
        $this->addSql('ALTER TABLE device DROP netflow_log');
    }
}
