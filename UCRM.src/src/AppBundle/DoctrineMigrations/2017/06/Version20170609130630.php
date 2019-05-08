<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170609130630 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql("UPDATE option SET code = 'NOTIFICATION_CREATED_DRAFTS_BY_EMAIL' WHERE code = 'NOTIFICATION_CREATED_DRAFTS'");

        $this->addSql("UPDATE option SET code = 'NOTIFICATION_CREATED_INVOICES_BY_EMAIL' WHERE code = 'NOTIFICATION_CREATED_INVOICES'");

        $this->addSql("INSERT INTO option(code, value) VALUES ('NOTIFICATION_CREATED_DRAFTS_IN_HEADER', 1)");

        $this->addSql("INSERT INTO option(code, value) VALUES ('NOTIFICATION_CREATED_INVOICES_IN_HEADER', 1)");
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql("UPDATE option SET code = 'NOTIFICATION_CREATED_DRAFTS' WHERE code = 'NOTIFICATION_CREATED_DRAFTS_BY_EMAIL'");

        $this->addSql("UPDATE option SET code = 'NOTIFICATION_CREATED_INVOICES' WHERE code = 'NOTIFICATION_CREATED_INVOICES_BY_EMAIL'");

        $this->addSql("DELETE FROM option WHERE code = 'NOTIFICATION_CREATED_DRAFTS_IN_HEADER'");

        $this->addSql("DELETE FROM option WHERE code = 'NOTIFICATION_CREATED_INVOICES_IN_HEADER'");
    }
}
