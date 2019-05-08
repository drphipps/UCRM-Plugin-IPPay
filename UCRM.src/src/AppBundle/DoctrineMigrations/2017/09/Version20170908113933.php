<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170908113933 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service RENAME COLUMN contract_length_months TO minimum_contract_length_months');
        $this->addSql('ALTER TABLE tariff RENAME COLUMN contract_length TO minimum_contract_length_months');
        $this->addSql('ALTER TABLE organization DROP contract_length_months');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tariff RENAME COLUMN minimum_contract_length_months TO contract_length');
        $this->addSql('ALTER TABLE service RENAME COLUMN minimum_contract_length_months TO contract_length_months');
        $this->addSql('ALTER TABLE organization ADD contract_length_months SMALLINT DEFAULT 12');
    }
}
