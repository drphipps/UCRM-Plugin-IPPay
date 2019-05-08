<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160818113359 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('UPDATE option SET validation_rules = ?, value = substring(value FROM 1 FOR 100) WHERE code = ?', ['{"length":100}', 'DISCOUNT_INVOICE_LABEL']);
    }

    public function down(Schema $schema)
    {
        $this->addSql('UPDATE option SET validation_rules = ? WHERE code = ?', [null, 'DISCOUNT_INVOICE_LABEL']);
    }
}
