<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170503122428 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client DROP title');
        $this->addSql('ALTER TABLE organization DROP vat_payer');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client ADD title VARCHAR(100) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN client.title IS \'Veneration or academic qualification\'');
        $this->addSql('ALTER TABLE organization ADD vat_payer INT DEFAULT NULL');
    }
}
