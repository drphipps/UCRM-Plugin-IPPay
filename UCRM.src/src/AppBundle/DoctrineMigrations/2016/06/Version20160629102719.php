<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160629102719 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE option SET validation_rules = \'["port"]\', type = \'int\', description = \'UCRM Server port. For example 8080, 80, etc. After change, make sure the UCRM server port matches the port used by docker and port used in your Firewall/NATrules used on your routers.\' WHERE option_id = 29');
        $this->addSql('UPDATE option SET validation_rules = \'["port"]\', type = \'int\', description = \'UCRM Server suspend port. For example 8081, 81, etc. After change, make sure the UCRM server port matches the port used by docker and port used in your Firewall/NATrules used on your routers.\' WHERE option_id = 30');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
        $this->addSql('UPDATE option SET validation_rules = NULL, type = \'string\', description = \'UCRM Server port. For example 8080, 80, etc.\' WHERE option_id = 29');
        $this->addSql('UPDATE option SET validation_rules = NULL, type = \'string\', description = \'UCRM Server suspend port. For example 8081, 81, etc.\' WHERE option_id = 30');
    }
}
