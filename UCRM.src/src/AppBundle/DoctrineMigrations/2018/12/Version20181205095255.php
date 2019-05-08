<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181205095255 extends AbstractMigration
{
    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice ADD taxable_supply_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN invoice.taxable_supply_date IS \'(DC2Type:datetime_immutable_utc)\'');
        /**
         * Set taxable_supply_date for existing invoices. Uses the same logic as InvoiceTaxableSupplyDateCalculator.
         *
         * @see InvoiceTaxableSupplyDateCalculator::computeTaxableSupplyDate()
         * ("NULL values in the list are ignored. The result will be NULL
         * only if all the expressions evaluate to NULL.")
         */
        $this->addSql('UPDATE invoice 
            SET taxable_supply_date=subquery.supply_date
            FROM (
                SELECT i.invoice_id, LEAST(i.created_date,MAX(iis.invoiced_to)) AS 
                  supply_date 
                    FROM invoice AS i
                    LEFT JOIN invoice_item AS ii 
                    ON ii.invoice_id = i.invoice_id
                    LEFT JOIN invoice_item_service AS iis
                    ON iis.item_id = ii.item_id
                    GROUP BY i.invoice_id
            ) AS subquery
            WHERE invoice.invoice_id = subquery.invoice_id
        ');
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice DROP taxable_supply_date');
    }
}
