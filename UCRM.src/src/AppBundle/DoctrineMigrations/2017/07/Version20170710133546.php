<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170710133546 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tariff ADD early_termination_fee DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD early_termination_fee_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD early_termination_fee_price DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD298F7D153 FOREIGN KEY (early_termination_fee_id) REFERENCES fee (fee_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E19D9AD298F7D153 ON service (early_termination_fee_id)');

        $this->addSql(
            'INSERT INTO option (code, value) VALUES (?, ?)',
            [
                'EARLY_TERMINATION_FEE_INVOICE_LABEL',
                'Early termination fee',
            ]
        );

        $this->addSql(
            'INSERT INTO option (code, value) VALUES (?, ?)',
            [
                'EARLY_TERMINATION_FEE_TAXABLE',
                '0',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tariff DROP early_termination_fee');
        $this->addSql('ALTER TABLE service DROP CONSTRAINT FK_E19D9AD298F7D153');
        $this->addSql('DROP INDEX UNIQ_E19D9AD298F7D153');
        $this->addSql('ALTER TABLE service DROP early_termination_fee_id');
        $this->addSql('ALTER TABLE service DROP early_termination_fee_price');

        $this->addSql(
            'DELETE FROM option WHERE code IN (?, ?)',
            [
                'EARLY_TERMINATION_FEE_INVOICE_LABEL',
                'EARLY_TERMINATION_FEE_TAXABLE',
            ]
        );
    }
}
