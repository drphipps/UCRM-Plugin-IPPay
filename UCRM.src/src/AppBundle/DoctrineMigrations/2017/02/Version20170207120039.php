<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170207120039 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client ALTER stop_service_due DROP NOT NULL');
        $this->addSql('ALTER TABLE client ALTER send_invoice_by_post DROP NOT NULL');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE client SET send_invoice_by_post = (SELECT value FROM option WHERE code = ?) WHERE send_invoice_by_post IS NULL', ['SEND_INVOICE_BY_POST']);
        $this->addSql('UPDATE client SET stop_service_due = (SELECT value FROM option WHERE code = ?) WHERE stop_service_due IS NULL', ['STOP_SERVICE_DUE']);

        $this->addSql('ALTER TABLE client ALTER send_invoice_by_post SET NOT NULL');
        $this->addSql('ALTER TABLE client ALTER stop_service_due SET NOT NULL');
    }
}
