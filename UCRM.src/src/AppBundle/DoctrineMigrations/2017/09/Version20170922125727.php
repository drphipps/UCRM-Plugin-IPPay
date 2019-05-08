<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170922125727 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO option(code, value) VALUES ('PDF_PAGE_SIZE_EXPORT', (SELECT value FROM option WHERE code = 'PDF_PAGE_SIZE'))");
        $this->addSql("INSERT INTO option(code, value) VALUES ('PDF_PAGE_SIZE_INVOICE', (SELECT value FROM option WHERE code = 'PDF_PAGE_SIZE'))");
        $this->addSql("INSERT INTO option(code, value) VALUES ('PDF_PAGE_SIZE_PAYMENT_RECEIPT', (SELECT value FROM option WHERE code = 'PDF_PAGE_SIZE'))");
        $this->addSql('DELETE FROM option WHERE code IN (?)', ['PDF_PAGE_SIZE']);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("INSERT INTO option(code, value) VALUES ('PDF_PAGE_SIZE', (SELECT value FROM option WHERE code = 'PDF_PAGE_SIZE_INVOICE'))");

        $this->addSql(
            'DELETE FROM option WHERE code IN (?, ?, ?)',
            [
                'PDF_PAGE_SIZE_EXPORT',
                'PDF_PAGE_SIZE_INVOICE',
                'PDF_PAGE_SIZE_PAYMENT_RECEIPT',
            ]
        );
    }
}
