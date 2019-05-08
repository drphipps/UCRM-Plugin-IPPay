<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181030150124 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE client_import_item_validation_errors (id UUID NOT NULL, user_ident JSON DEFAULT \'[]\' NOT NULL, first_name JSON DEFAULT \'[]\' NOT NULL, last_name JSON DEFAULT \'[]\' NOT NULL, name_for_view JSON DEFAULT \'[]\' NOT NULL, username JSON DEFAULT \'[]\' NOT NULL, company_name JSON DEFAULT \'[]\' NOT NULL, is_lead JSON DEFAULT \'[]\' NOT NULL, address_gps_lat JSON DEFAULT \'[]\' NOT NULL, address_gps_lon JSON DEFAULT \'[]\' NOT NULL, company_registration_number JSON DEFAULT \'[]\' NOT NULL, tax1 JSON DEFAULT \'[]\' NOT NULL, tax2 JSON DEFAULT \'[]\' NOT NULL, tax3 JSON DEFAULT \'[]\' NOT NULL, company_tax_id JSON DEFAULT \'[]\' NOT NULL, company_website JSON DEFAULT \'[]\' NOT NULL, email1 JSON DEFAULT \'[]\' NOT NULL, email2 JSON DEFAULT \'[]\' NOT NULL, email3 JSON DEFAULT \'[]\' NOT NULL, emails JSON DEFAULT \'[]\' NOT NULL, phone1 JSON DEFAULT \'[]\' NOT NULL, phone2 JSON DEFAULT \'[]\' NOT NULL, phone3 JSON DEFAULT \'[]\' NOT NULL, phones JSON DEFAULT \'[]\' NOT NULL, street1 JSON DEFAULT \'[]\' NOT NULL, street2 JSON DEFAULT \'[]\' NOT NULL, city JSON DEFAULT \'[]\' NOT NULL, country JSON DEFAULT \'[]\' NOT NULL, state JSON DEFAULT \'[]\' NOT NULL, zip_code JSON DEFAULT \'[]\' NOT NULL, invoice_street1 JSON DEFAULT \'[]\' NOT NULL, invoice_street2 JSON DEFAULT \'[]\' NOT NULL, invoice_city JSON DEFAULT \'[]\' NOT NULL, invoice_country JSON DEFAULT \'[]\' NOT NULL, invoice_state JSON DEFAULT \'[]\' NOT NULL, invoice_zip_code JSON DEFAULT \'[]\' NOT NULL, registration_date JSON DEFAULT \'[]\' NOT NULL, client_note JSON DEFAULT \'[]\' NOT NULL, unmapped_errors JSON DEFAULT \'[]\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE client_import (id UUID NOT NULL, user_id INT DEFAULT NULL, organization_id INT DEFAULT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status SMALLINT DEFAULT 1 NOT NULL, csv_hash VARCHAR(40) NOT NULL, csv_mapping JSON DEFAULT NULL, csv_delimiter VARCHAR(1) DEFAULT \',\' NOT NULL, csv_enclosure VARCHAR(1) DEFAULT \'"\' NOT NULL, csv_escape VARCHAR(1) DEFAULT \'\\\' NOT NULL, csv_has_header BOOLEAN DEFAULT \'true\' NOT NULL, count INT DEFAULT NULL, count_success INT DEFAULT 0 NOT NULL, count_failure INT DEFAULT 0 NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C8E885AEA76ED395 ON client_import (user_id)');
        $this->addSql('CREATE INDEX IDX_C8E885AE32C8A3DE ON client_import (organization_id)');
        $this->addSql('COMMENT ON COLUMN client_import.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('CREATE TABLE service_import_item (id UUID NOT NULL, import_item_id UUID NOT NULL, validation_errors_id UUID DEFAULT NULL, line_number INT NOT NULL, tariff TEXT DEFAULT NULL, invoice_label TEXT DEFAULT NULL, note TEXT DEFAULT NULL, tariff_period TEXT DEFAULT NULL, individual_price TEXT DEFAULT NULL, active_from TEXT DEFAULT NULL, active_to TEXT DEFAULT NULL, invoicing_start TEXT DEFAULT NULL, invoicing_period_type TEXT DEFAULT NULL, invoicing_period_start_day TEXT DEFAULT NULL, invoicing_days_in_advance TEXT DEFAULT NULL, invoice_separately TEXT DEFAULT NULL, invoice_use_credit TEXT DEFAULT NULL, invoice_approve_send_auto TEXT DEFAULT NULL, fcc_block_id TEXT DEFAULT NULL, contract_id TEXT DEFAULT NULL, contract_type TEXT DEFAULT NULL, contract_end_date TEXT DEFAULT NULL, minimum_contract_length_months TEXT DEFAULT NULL, setup_fee TEXT DEFAULT NULL, early_termination_fee TEXT DEFAULT NULL, tax1 TEXT DEFAULT NULL, tax2 TEXT DEFAULT NULL, tax3 TEXT DEFAULT NULL, address_gps_lat TEXT DEFAULT NULL, address_gps_lon TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3EADED3217FFBB2 ON service_import_item (import_item_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3EADED32741C8B98 ON service_import_item (validation_errors_id)');
        $this->addSql('CREATE TABLE service_import_item_validation_errors (id UUID NOT NULL, tariff JSON DEFAULT \'[]\' NOT NULL, invoice_label JSON DEFAULT \'[]\' NOT NULL, note JSON DEFAULT \'[]\' NOT NULL, tariff_period JSON DEFAULT \'[]\' NOT NULL, individual_price JSON DEFAULT \'[]\' NOT NULL, active_from JSON DEFAULT \'[]\' NOT NULL, active_to JSON DEFAULT \'[]\' NOT NULL, invoicing_start JSON DEFAULT \'[]\' NOT NULL, invoicing_period_type JSON DEFAULT \'[]\' NOT NULL, invoicing_period_start_day JSON DEFAULT \'[]\' NOT NULL, invoicing_days_in_advance JSON DEFAULT \'[]\' NOT NULL, invoice_separately JSON DEFAULT \'[]\' NOT NULL, invoice_use_credit JSON DEFAULT \'[]\' NOT NULL, invoice_approve_send_auto JSON DEFAULT \'[]\' NOT NULL, fcc_block_id JSON DEFAULT \'[]\' NOT NULL, contract_id JSON DEFAULT \'[]\' NOT NULL, contract_type JSON DEFAULT \'[]\' NOT NULL, contract_end_date JSON DEFAULT \'[]\' NOT NULL, minimum_contract_length_months JSON DEFAULT \'[]\' NOT NULL, setup_fee JSON DEFAULT \'[]\' NOT NULL, early_termination_fee JSON DEFAULT \'[]\' NOT NULL, tax1 JSON DEFAULT \'[]\' NOT NULL, tax2 JSON DEFAULT \'[]\' NOT NULL, tax3 JSON DEFAULT \'[]\' NOT NULL, address_gps_lat JSON DEFAULT \'[]\' NOT NULL, address_gps_lon JSON DEFAULT \'[]\' NOT NULL, unmapped_errors JSON DEFAULT \'[]\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE client_import_item (id UUID NOT NULL, import_id UUID NOT NULL, validation_errors_id UUID DEFAULT NULL, line_number INT NOT NULL, empty BOOLEAN DEFAULT \'false\' NOT NULL, user_ident TEXT DEFAULT NULL, first_name TEXT DEFAULT NULL, last_name TEXT DEFAULT NULL, name_for_view TEXT DEFAULT NULL, username TEXT DEFAULT NULL, company_name TEXT DEFAULT NULL, is_lead TEXT DEFAULT NULL, address_gps_lat TEXT DEFAULT NULL, address_gps_lon TEXT DEFAULT NULL, company_registration_number TEXT DEFAULT NULL, tax1 TEXT DEFAULT NULL, tax2 TEXT DEFAULT NULL, tax3 TEXT DEFAULT NULL, company_tax_id TEXT DEFAULT NULL, company_website TEXT DEFAULT NULL, email1 TEXT DEFAULT NULL, email2 TEXT DEFAULT NULL, email3 TEXT DEFAULT NULL, emails TEXT DEFAULT NULL, phone1 TEXT DEFAULT NULL, phone2 TEXT DEFAULT NULL, phone3 TEXT DEFAULT NULL, phones TEXT DEFAULT NULL, street1 TEXT DEFAULT NULL, street2 TEXT DEFAULT NULL, city TEXT DEFAULT NULL, country TEXT DEFAULT NULL, state TEXT DEFAULT NULL, zip_code TEXT DEFAULT NULL, invoice_street1 TEXT DEFAULT NULL, invoice_street2 TEXT DEFAULT NULL, invoice_city TEXT DEFAULT NULL, invoice_country TEXT DEFAULT NULL, invoice_state TEXT DEFAULT NULL, invoice_zip_code TEXT DEFAULT NULL, registration_date TEXT DEFAULT NULL, client_note TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F37CEEA5B6A263D9 ON client_import_item (import_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F37CEEA5741C8B98 ON client_import_item (validation_errors_id)');
        $this->addSql('ALTER TABLE client_import ADD CONSTRAINT FK_C8E885AEA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE client_import ADD CONSTRAINT FK_C8E885AE32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (organization_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_import_item ADD CONSTRAINT FK_3EADED3217FFBB2 FOREIGN KEY (import_item_id) REFERENCES client_import_item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_import_item ADD CONSTRAINT FK_3EADED32741C8B98 FOREIGN KEY (validation_errors_id) REFERENCES service_import_item_validation_errors (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE client_import_item ADD CONSTRAINT FK_F37CEEA5B6A263D9 FOREIGN KEY (import_id) REFERENCES client_import (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE client_import_item ADD CONSTRAINT FK_F37CEEA5741C8B98 FOREIGN KEY (validation_errors_id) REFERENCES client_import_item_validation_errors (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        // As this is not really important and migration from JSON array would be serious pain, just delete it.
        $this->addSql('DELETE FROM csv_import_structure');
        $this->addSql('ALTER TABLE csv_import_structure ADD csv_delimiter VARCHAR(1) DEFAULT \',\' NOT NULL');
        $this->addSql('ALTER TABLE csv_import_structure ADD csv_enclosure VARCHAR(1) DEFAULT \'"\' NOT NULL');
        $this->addSql('ALTER TABLE csv_import_structure ADD csv_escape VARCHAR(1) DEFAULT \'\\\' NOT NULL');
        $this->addSql('ALTER TABLE csv_import_structure DROP structure');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client_import_item DROP CONSTRAINT FK_F37CEEA5741C8B98');
        $this->addSql('ALTER TABLE client_import_item DROP CONSTRAINT FK_F37CEEA5B6A263D9');
        $this->addSql('ALTER TABLE service_import_item DROP CONSTRAINT FK_3EADED32741C8B98');
        $this->addSql('ALTER TABLE service_import_item DROP CONSTRAINT FK_3EADED3217FFBB2');
        $this->addSql('DROP TABLE client_import_item_validation_errors');
        $this->addSql('DROP TABLE client_import');
        $this->addSql('DROP TABLE service_import_item');
        $this->addSql('DROP TABLE service_import_item_validation_errors');
        $this->addSql('DROP TABLE client_import_item');

        // As this is not really important and migration from JSON array would be serious pain, just delete it.
        $this->addSql('DELETE FROM csv_import_structure');
        $this->addSql('ALTER TABLE csv_import_structure ADD structure JSON NOT NULL');
        $this->addSql('ALTER TABLE csv_import_structure DROP csv_delimiter');
        $this->addSql('ALTER TABLE csv_import_structure DROP csv_enclosure');
        $this->addSql('ALTER TABLE csv_import_structure DROP csv_escape');
    }
}
