<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170403141457 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice ADD tax_rounding SMALLINT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE invoice ALTER tax_rounding DROP DEFAULT');
        $this->addSql('ALTER TABLE invoice ADD pricing_mode SMALLINT NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE invoice ALTER pricing_mode DROP DEFAULT');
        $this->addSql('ALTER TABLE invoice ADD tax_coefficient_precision INT DEFAULT NULL');

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                69,
                'PRICING_MODE',
                '1',
            ]
        );

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, NULL)',
            [
                70,
                'PRICING_TAX_COEFFICIENT_PRECISION',
            ]
        );

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                71,
                'PRICING_MULTIPLE_TAXES',
                '1',
            ]
        );

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                72,
                'INVOICE_TAX_ROUNDING',
                '0',
            ]
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice DROP tax_rounding');
        $this->addSql('ALTER TABLE invoice DROP pricing_mode');
        $this->addSql('ALTER TABLE invoice DROP tax_coefficient_precision');

        $this->addSql('DELETE FROM option WHERE option_id IN (69, 70, 71, 72)');
    }
}
