<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160921095451 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service DROP CONSTRAINT fk_e19d9ad2ab0be982');
        $this->addSql('DROP INDEX idx_e19d9ad2ab0be982');
        $this->addSql('ALTER TABLE service DROP interface_id');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service ADD interface_id INT NOT NULL');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT fk_e19d9ad2ab0be982 FOREIGN KEY (interface_id) REFERENCES device_interface (interface_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_e19d9ad2ab0be982 ON service (interface_id)');
    }
}
