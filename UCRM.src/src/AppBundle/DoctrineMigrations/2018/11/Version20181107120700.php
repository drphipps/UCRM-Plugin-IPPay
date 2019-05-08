<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181107120700 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->prefixDuplicates();

        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT invoice_number_organization_id_key UNIQUE (invoice_number, organization_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ONLY invoice DROP CONSTRAINT invoice_number_organization_id_key');
    }

    private function prefixDuplicates(): void
    {
        $this->connection->transactional(
            function (Connection $connection) {
                $duplicateInvoices = $connection->createQueryBuilder()
                    ->select('invoice_number')
                    ->from('invoice')
                    ->groupBy('invoice_number, organization_id')
                    ->having('COUNT(*) > 1')
                    ->execute()
                    ->fetchAll();

                foreach ($duplicateInvoices as $duplicateInvoice) {
                    $duplicateNumber = $duplicateInvoice['invoice_number'];
                    $invoices = $connection->createQueryBuilder()
                        ->select('invoice_id')
                        ->from('invoice')
                        ->where('invoice_number = :invoiceNumber')
                        ->setParameter('invoiceNumber', $duplicateNumber)
                        ->orderBy('invoice_id')
                        ->execute()
                        ->fetchAll();

                    $counter = 0;
                    foreach ($invoices as $invoice) {
                        if (! $counter) {
                            $counter = 1;
                            continue;
                        }
                        $connection->executeUpdate(
                            'UPDATE invoice SET invoice_number = ? WHERE invoice_id = ?',
                            [
                                sprintf('D%02s_%s', $counter, $duplicateNumber),
                                $invoice['invoice_id'],
                            ]
                        );

                        ++$counter;
                    }
                }
            }
        );
    }
}
