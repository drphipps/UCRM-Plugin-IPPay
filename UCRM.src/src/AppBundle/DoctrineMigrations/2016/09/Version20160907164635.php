<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160907164635 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('UPDATE option SET description = \'Please enter email address of this SMTP account. This address is used in Sender attribute in email header. Additionally, all UCRM system notifications are sent to this address.\' WHERE code = \'MAILER_SENDER_ADDRESS\';');
        $this->addSql('UPDATE option SET description = \'UCRM Server port. For example 8080, 80, etc. After change, make sure the UCRM server port matches the port used by docker and port used in your Firewall/NAT rules used on your routers.\' WHERE option_id = 29');
        $this->addSql('UPDATE option SET description = \'UCRM Server suspend port. For example 8081, 81, etc. After change, make sure the UCRM server port matches the port used by docker and port used in your Firewall/NAT rules used on your routers.\' WHERE option_id = 30');
        $this->addSql('UPDATE option SET description = \'UCRM Server port. For example 80, 8080 (or 443, 8443 if you use https). After change, make sure this port number matches the port used by docker and port used in your EdgeOs Firewall/NAT rules.\' WHERE code = \'SERVER_PORT\'');
        $this->addSql('UPDATE option SET description = \'UCRM Server suspend port. For example 8081, 81. After change, make sure this port number matches the port used by docker and port used in your EdgeOs Firewall/NAT rules.\' WHERE code = \'SERVER_SUSPEND_PORT\'');
    }

    public function down(Schema $schema)
    {
        $this->addSql('UPDATE option SET description = \'This email address is used to send all UCRM mail messages, i.e. it\'\'s used as the "Sender attribute". Besides this, all UCRM system notifications are sent to this address.\'WHERE code = \'MAILER_SENDER_ADDRESS\';');
        $this->addSql('UPDATE option SET description = \'UCRM Server port. For example 8080, 80, etc. After change, make sure the UCRM server port matches the port used by docker and port used in your Firewall/NATrules used on your routers.\' WHERE option_id = 29');
        $this->addSql('UPDATE option SET description = \'UCRM Server suspend port. For example 8081, 81, etc. After change, make sure the UCRM server port matches the port used by docker and port used in your Firewall/NATrules used on your routers.\' WHERE option_id = 30');
        $this->addSql('UPDATE option SET description = \'UCRM Server port. For example 80, 8080 (or 443, 8443 if you use https). After change, make sure this port number matches the port used by docker and port used in your EdgeOs Firewall/NATrules.\' WHERE code = \'SERVER_PORT\'');
        $this->addSql('UPDATE option SET description = \'UCRM Server suspend port. For example 8081, 81. After change, make sure this port number matches the port used by docker and port used in your EdgeOs Firewall/NATrules.\' WHERE code = \'SERVER_SUSPEND_PORT\'');
    }
}
