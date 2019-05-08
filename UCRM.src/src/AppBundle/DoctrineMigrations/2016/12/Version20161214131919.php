<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20161214131919 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice ALTER organization_bank_account_field1 TYPE VARCHAR(100)');
        $this->addSql('ALTER TABLE invoice ALTER organization_bank_account_field2 TYPE VARCHAR(100)');
        $this->addSql('ALTER TABLE organization ADD invoice_template_default_notes TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE organization_bank_account ALTER field1 TYPE VARCHAR(100)');
        $this->addSql('ALTER TABLE organization_bank_account ALTER field2 TYPE VARCHAR(100)');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization_bank_account ALTER field1 TYPE VARCHAR(9)');
        $this->addSql('ALTER TABLE organization_bank_account ALTER field2 TYPE VARCHAR(17)');
        $this->addSql('ALTER TABLE invoice ALTER organization_bank_account_field1 TYPE VARCHAR(9)');
        $this->addSql('ALTER TABLE invoice ALTER organization_bank_account_field2 TYPE VARCHAR(17)');
        $this->addSql('ALTER TABLE organization DROP invoice_template_default_notes');
    }
}
