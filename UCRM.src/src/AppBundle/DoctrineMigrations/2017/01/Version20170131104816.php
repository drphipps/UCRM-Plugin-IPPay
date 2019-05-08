<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170131104816 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice ALTER client_invoice_address_same_as_contact TYPE BOOLEAN USING client_invoice_address_same_as_contact::boolean');
        $this->addSql('ALTER TABLE invoice ALTER client_invoice_address_same_as_contact DROP DEFAULT');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice ALTER client_invoice_address_same_as_contact TYPE INT USING client_invoice_address_same_as_contact::integer');
        $this->addSql('ALTER TABLE invoice ALTER client_invoice_address_same_as_contact DROP DEFAULT');
    }
}
