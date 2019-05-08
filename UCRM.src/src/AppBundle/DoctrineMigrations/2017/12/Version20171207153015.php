<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171207153015 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization ADD quote_template_include_bank_account BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE organization ADD quote_template_include_tax_information BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE organization ADD quote_template_default_notes TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization DROP quote_template_include_bank_account');
        $this->addSql('ALTER TABLE organization DROP quote_template_include_tax_information');
        $this->addSql('ALTER TABLE organization DROP quote_template_default_notes');
    }
}
