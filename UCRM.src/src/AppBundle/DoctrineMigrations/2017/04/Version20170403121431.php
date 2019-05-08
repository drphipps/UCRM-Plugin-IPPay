<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170403121431 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice RENAME COLUMN tax_rounding TO item_rounding');
        $this->addSql('UPDATE option SET code = ? WHERE option_id = 50', ['INVOICE_ITEM_ROUNDING']);
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice RENAME COLUMN item_rounding TO tax_rounding');
        $this->addSql('UPDATE option SET code = ? WHERE option_id = 50', ['INVOICE_TAX_ROUNDING']);
    }
}
