<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160913111501 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE device_qos (device_id INT NOT NULL, parent_device_id INT NOT NULL, PRIMARY KEY(device_id, parent_device_id))');
        $this->addSql('CREATE INDEX IDX_6BE5560294A4C7D4 ON device_qos (device_id)');
        $this->addSql('CREATE INDEX IDX_6BE5560265EFE83A ON device_qos (parent_device_id)');
        $this->addSql('CREATE TABLE service_device_qos (service_device_id INT NOT NULL, parent_device_id INT NOT NULL, PRIMARY KEY(service_device_id, parent_device_id))');
        $this->addSql('CREATE INDEX IDX_FDB46FB6CC35FD9E ON service_device_qos (service_device_id)');
        $this->addSql('CREATE INDEX IDX_FDB46FB665EFE83A ON service_device_qos (parent_device_id)');
        $this->addSql('ALTER TABLE device_qos ADD CONSTRAINT FK_6BE5560294A4C7D4 FOREIGN KEY (device_id) REFERENCES device (device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE device_qos ADD CONSTRAINT FK_6BE5560265EFE83A FOREIGN KEY (parent_device_id) REFERENCES device (device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_device_qos ADD CONSTRAINT FK_FDB46FB6CC35FD9E FOREIGN KEY (service_device_id) REFERENCES service_device (service_device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_device_qos ADD CONSTRAINT FK_FDB46FB665EFE83A FOREIGN KEY (parent_device_id) REFERENCES device (device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE device ADD qos_enabled INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE service_device ADD qos_enabled INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE device_qos');
        $this->addSql('DROP TABLE service_device_qos');
        $this->addSql('ALTER TABLE device DROP qos_enabled');
        $this->addSql('ALTER TABLE service_device DROP qos_enabled');
    }
}
