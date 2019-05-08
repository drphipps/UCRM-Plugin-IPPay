<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170116092613 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                50,
                'INVOICE_TAX_ROUNDING',
                '0',
            ]
        );

        $this->addSql('ALTER TABLE invoice ADD tax_rounding SMALLINT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE invoice ALTER tax_rounding DROP DEFAULT');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE option_id = 50');

        $this->addSql('ALTER TABLE invoice DROP tax_rounding');
    }
}
