<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160822140753 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE search_device_queue (device_id INT NOT NULL, PRIMARY KEY(device_id))');
        $this->addSql('CREATE TABLE search_service_device_queue (device_id INT NOT NULL, PRIMARY KEY(device_id))');
        $this->addSql('ALTER TABLE search_device_queue ADD CONSTRAINT FK_AEB448DA94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE search_service_device_queue ADD CONSTRAINT FK_AFCD265994A4C7D4 FOREIGN KEY (device_id) REFERENCES service_device (service_device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE search_device_queue');
        $this->addSql('DROP TABLE search_service_device_queue');
    }
}
