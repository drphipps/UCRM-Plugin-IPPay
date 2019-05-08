<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160826000000 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE option SET description = \'UCRM Server port. For example 80, 8080 (or 443, 8443 if you use https). After change, make sure this port number matches the port used by docker and port used in your EdgeOs Firewall/NAT rules.\' WHERE code = \'SERVER_PORT\'');
        $this->addSql('UPDATE option SET description = \'UCRM Server suspend port. For example 8081, 81. After change, make sure this port number matches the port used by docker and port used in your EdgeOs Firewall/NAT rules.\' WHERE code = \'SERVER_SUSPEND_PORT\'');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE option SET description = \'UCRM Server port. For example 8080, 80, etc. After change, make sure the UCRM server port matches the port used by docker and port used in your Firewall/NATrules used on your routers.\' WHERE code = \'SERVER_PORT\'');
        $this->addSql('UPDATE option SET description = \'UCRM Server suspend port. For example 8081, 81, etc. After change, make sure the UCRM server port matches the port used by docker and port used in your Firewall/NATrules used on your routers.\' WHERE code = \'SERVER_SUSPEND_PORT\'');
    }
}
