<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170703090200 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tariff ADD contract_length INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tariff ADD setup_fee DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE fee ADD type SMALLINT NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE fee ALTER type DROP DEFAULT');
        $this->addSql('DROP INDEX idx_e19d9ad2d1b13435');
        $this->addSql('ALTER TABLE service ADD setup_fee_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD22F5E0BCE FOREIGN KEY (setup_fee_id) REFERENCES fee (fee_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E19D9AD22F5E0BCE ON service (setup_fee_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E19D9AD2D1B13435 ON service (superseded_by_service_id)');

        $this->addSql(
            'INSERT INTO option (code, value) VALUES (?, ?)',
            [
                'SETUP_FEE_INVOICE_LABEL',
                'Setup fee',
            ]
        );

        $this->addSql(
            'INSERT INTO option (code, value) VALUES (?, ?)',
            [
                'SETUP_FEE_TAXABLE',
                '0',
            ]
        );

        $this->addSql('UPDATE user_group_permission SET module_name = \'AppBundle\Controller\SettingFeesController\' WHERE module_name = \'AppBundle\Controller\SettingLateFeeController\'');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tariff DROP contract_length');
        $this->addSql('ALTER TABLE tariff DROP setup_fee');
        $this->addSql('ALTER TABLE fee DROP type');
        $this->addSql('ALTER TABLE service DROP CONSTRAINT FK_E19D9AD22F5E0BCE');
        $this->addSql('DROP INDEX UNIQ_E19D9AD22F5E0BCE');
        $this->addSql('DROP INDEX UNIQ_E19D9AD2D1B13435');
        $this->addSql('ALTER TABLE service DROP setup_fee_id');
        $this->addSql('CREATE INDEX idx_e19d9ad2d1b13435 ON service (superseded_by_service_id)');

        $this->addSql(
            'DELETE FROM option WHERE code IN (?, ?)',
            [
                'SETUP_FEE_INVOICE_LABEL',
                'SETUP_FEE_TAXABLE',
            ]
        );

        $this->addSql('UPDATE user_group_permission SET module_name = \'AppBundle\Controller\SettingLateFeeController\' WHERE module_name = \'AppBundle\Controller\SettingFeesController\'');
    }
}
