<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170424103936 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice ADD total_untaxed DOUBLE PRECISION NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE invoice ADD subtotal DOUBLE PRECISION NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE invoice ADD total_discount DOUBLE PRECISION NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE invoice ADD total_tax_amount DOUBLE PRECISION NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE invoice ADD total_taxes JSON NOT NULL DEFAULT \'[]\'');

        $this->addSql('ALTER TABLE invoice ALTER total_untaxed DROP DEFAULT');
        $this->addSql('ALTER TABLE invoice ALTER subtotal DROP DEFAULT');
        $this->addSql('ALTER TABLE invoice ALTER total_discount DROP DEFAULT');
        $this->addSql('ALTER TABLE invoice ALTER total_tax_amount DROP DEFAULT');
        $this->addSql('ALTER TABLE invoice ALTER total_taxes DROP DEFAULT');

        $this->addSql('INSERT INTO general (code, value) VALUES (\'invoice_totals_migration_complete\', \'0\')');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice DROP total_untaxed');
        $this->addSql('ALTER TABLE invoice DROP subtotal');
        $this->addSql('ALTER TABLE invoice DROP total_discount');
        $this->addSql('ALTER TABLE invoice DROP total_tax_amount');
        $this->addSql('ALTER TABLE invoice DROP total_taxes');

        $this->addSql('DELETE FROM general WHERE code = \'invoice_totals_migration_complete\'');
    }
}
