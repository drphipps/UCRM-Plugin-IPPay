<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160809123949 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE service_ip SET first_ip_address = ip_address WHERE first_ip_address IS NULL OR type = 1');
        $this->addSql('UPDATE service_ip SET last_ip_address = ip_address WHERE last_ip_address IS NULL OR type = 1');
        $this->addSql('ALTER TABLE service_ip ALTER first_ip_address SET NOT NULL');
        $this->addSql('ALTER TABLE service_ip ALTER last_ip_address SET NOT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_ip ALTER first_ip_address DROP NOT NULL');
        $this->addSql('ALTER TABLE service_ip ALTER last_ip_address DROP NOT NULL');
    }
}
