<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170620150146 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice ADD late_fee_created BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql(
            '
                UPDATE invoice
                SET late_fee_created = true
                WHERE
                invoice_id IN (
                  SELECT due_invoice_id
                  FROM fee
                )
            '
        );
        $this->addSql(
            '
                DELETE FROM fee a
                USING (
                    SELECT MIN(fee_id) AS fee_id, due_invoice_id
                    FROM fee
                    GROUP BY due_invoice_id HAVING COUNT(*) > 1
                ) b
                WHERE a.due_invoice_id = b.due_invoice_id
                AND a.fee_id <> b.fee_id
                AND a.invoiced = false
            '
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice DROP late_fee_created');
    }
}
