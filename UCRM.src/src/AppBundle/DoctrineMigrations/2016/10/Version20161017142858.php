<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161017142858 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device_interface_ip ALTER netmask TYPE SMALLINT');
        $this->addSql('ALTER TABLE device_interface_ip ALTER netmask DROP DEFAULT');
        $this->addSql('ALTER TABLE service_ip ALTER netmask TYPE SMALLINT');
        $this->addSql('ALTER TABLE service_ip ALTER netmask DROP DEFAULT');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device_interface_ip ALTER netmask TYPE INT');
        $this->addSql('ALTER TABLE device_interface_ip ALTER netmask DROP DEFAULT');
        $this->addSql('ALTER TABLE service_ip ALTER netmask TYPE INT');
        $this->addSql('ALTER TABLE service_ip ALTER netmask DROP DEFAULT');
    }
}
