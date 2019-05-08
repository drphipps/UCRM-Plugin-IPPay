<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20161114132011 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client ADD has_suspended_service BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE client ADD has_outage BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE client ADD has_overdue_invoice BOOLEAN DEFAULT \'false\' NOT NULL');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client DROP has_suspended_service');
        $this->addSql('ALTER TABLE client DROP has_outage');
        $this->addSql('ALTER TABLE client DROP has_overdue_invoice');
    }
}
