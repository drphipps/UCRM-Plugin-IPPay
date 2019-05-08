<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160602161553 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE option ADD validation_rules JSON DEFAULT NULL');
        $this->addSql('UPDATE option SET validation_rules = \'["ip"]\' WHERE option_id = 19'); // SERVER_IP
        $this->addSql('UPDATE option SET validation_rules = \'["email"]\' WHERE option_id = 20'); // SUPPORT_EMAIL_ADDRESS
        $this->addSql('UPDATE option SET validation_rules = \'["fqdn"]\' WHERE option_id = 26'); // SERVER_FQDN
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE option DROP validation_rules');
    }
}
