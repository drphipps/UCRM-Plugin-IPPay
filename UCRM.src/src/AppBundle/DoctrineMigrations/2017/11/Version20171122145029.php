<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171122145029 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice_item_service DROP period');
        $this->addSql('CREATE TABLE quote_item (item_id SERIAL NOT NULL, quote_id INT NOT NULL, tax_id1 INT DEFAULT NULL, tax_id2 INT DEFAULT NULL, tax_id3 INT DEFAULT NULL, label VARCHAR(500) NOT NULL, quantity DOUBLE PRECISION NOT NULL, price DOUBLE PRECISION NOT NULL, total DOUBLE PRECISION NOT NULL, taxable BOOLEAN DEFAULT \'false\' NOT NULL, tax_rate1 DOUBLE PRECISION DEFAULT NULL, tax_rate2 DOUBLE PRECISION DEFAULT NULL, tax_rate3 DOUBLE PRECISION DEFAULT NULL, discr VARCHAR(255) NOT NULL, PRIMARY KEY(item_id))');
        $this->addSql('CREATE INDEX IDX_8DFC7A94DB805178 ON quote_item (quote_id)');
        $this->addSql('CREATE INDEX IDX_8DFC7A94B661D75 ON quote_item (tax_id1)');
        $this->addSql('CREATE INDEX IDX_8DFC7A94926F4CCF ON quote_item (tax_id2)');
        $this->addSql('CREATE INDEX IDX_8DFC7A94E5687C59 ON quote_item (tax_id3)');
        $this->addSql('CREATE TABLE quote_item_product (item_id INT NOT NULL, product_id INT DEFAULT NULL, unit VARCHAR(50) DEFAULT NULL, PRIMARY KEY(item_id))');
        $this->addSql('CREATE INDEX IDX_6FF67DFF4584665A ON quote_item_product (product_id)');
        $this->addSql('CREATE TABLE quote_item_service (item_id INT NOT NULL, service_id INT DEFAULT NULL, original_service_id INT DEFAULT NULL, discount_type INT DEFAULT 0 NOT NULL, discount_value DOUBLE PRECISION DEFAULT NULL, discount_invoice_label VARCHAR(100) DEFAULT NULL, discount_from DATE DEFAULT NULL, discount_to DATE DEFAULT NULL, discount_quantity DOUBLE PRECISION DEFAULT NULL, discount_price DOUBLE PRECISION DEFAULT NULL, discount_total DOUBLE PRECISION DEFAULT NULL, invoiced_from DATE DEFAULT NULL, invoiced_to DATE DEFAULT NULL, PRIMARY KEY(item_id))');
        $this->addSql('CREATE INDEX IDX_5D21E380ED5CA9E6 ON quote_item_service (service_id)');
        $this->addSql('CREATE INDEX IDX_5D21E38080EBDE72 ON quote_item_service (original_service_id)');
        $this->addSql('CREATE TABLE quote_item_surcharge (item_id INT NOT NULL, service_surcharge_id INT DEFAULT NULL, service_id INT DEFAULT NULL, PRIMARY KEY(item_id))');
        $this->addSql('CREATE INDEX IDX_F59DFA2D39451620 ON quote_item_surcharge (service_surcharge_id)');
        $this->addSql('CREATE INDEX IDX_F59DFA2DED5CA9E6 ON quote_item_surcharge (service_id)');
        $this->addSql('CREATE TABLE quote_item_other (item_id INT NOT NULL, unit VARCHAR(50) DEFAULT NULL, PRIMARY KEY(item_id))');
        $this->addSql('CREATE TABLE quote (id SERIAL NOT NULL, client_id INT NOT NULL, client_invoice_country_id INT DEFAULT NULL, organization_id INT DEFAULT NULL, organization_state_id INT DEFAULT NULL, organization_country_id INT DEFAULT NULL, client_state_id INT DEFAULT NULL, client_country_id INT DEFAULT NULL, client_invoice_state_id INT DEFAULT NULL, currency_id INT NOT NULL, quote_number VARCHAR(60) DEFAULT NULL, total DOUBLE PRECISION NOT NULL, amount_paid DOUBLE PRECISION DEFAULT \'0\' NOT NULL, discount_type INT DEFAULT 0 NOT NULL, discount_value DOUBLE PRECISION DEFAULT NULL, discount_invoice_label VARCHAR(100) DEFAULT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, invoice_maturity_days INT DEFAULT NULL, pdf_path VARCHAR(500) DEFAULT NULL, client_first_name VARCHAR(255) DEFAULT NULL, client_last_name VARCHAR(255) DEFAULT NULL, client_company_name VARCHAR(255) DEFAULT NULL, client_street1 VARCHAR(250) NOT NULL, client_street2 VARCHAR(250) DEFAULT NULL, client_city VARCHAR(250) NOT NULL, client_zip_code VARCHAR(20) NOT NULL, client_company_registration_number VARCHAR(50) DEFAULT NULL, client_company_tax_id VARCHAR(50) DEFAULT NULL, client_phone VARCHAR(50) DEFAULT NULL, client_email VARCHAR(320) DEFAULT NULL, client_invoice_street1 VARCHAR(250) DEFAULT NULL, client_invoice_street2 VARCHAR(250) DEFAULT NULL, client_invoice_city VARCHAR(250) DEFAULT NULL, client_invoice_zip_code VARCHAR(20) DEFAULT NULL, client_invoice_address_same_as_contact BOOLEAN DEFAULT NULL, organization_name VARCHAR(255) NOT NULL, organization_registration_number VARCHAR(50) DEFAULT NULL, organization_tax_id VARCHAR(50) DEFAULT NULL, organization_email VARCHAR(320) DEFAULT \'\' NOT NULL, organization_phone VARCHAR(50) DEFAULT NULL, organization_website VARCHAR(400) DEFAULT NULL, organization_street1 VARCHAR(250) DEFAULT \'\' NOT NULL, organization_street2 VARCHAR(250) DEFAULT NULL, organization_city VARCHAR(250) DEFAULT \'\' NOT NULL, organization_zip_code VARCHAR(20) DEFAULT \'\' NOT NULL, organization_bank_account_field1 VARCHAR(100) DEFAULT NULL, organization_bank_account_field2 VARCHAR(100) DEFAULT NULL, organization_bank_account_name VARCHAR(50) DEFAULT NULL, organization_logo_path VARCHAR(500) DEFAULT \'\', organization_stamp_path VARCHAR(500) DEFAULT \'\', template_include_bank_account BOOLEAN DEFAULT \'false\' NOT NULL, template_include_tax_information BOOLEAN DEFAULT \'false\' NOT NULL, item_rounding SMALLINT NOT NULL, tax_rounding SMALLINT NOT NULL, pricing_mode SMALLINT NOT NULL, tax_coefficient_precision INT DEFAULT NULL, total_untaxed DOUBLE PRECISION NOT NULL, subtotal DOUBLE PRECISION NOT NULL, total_discount DOUBLE PRECISION NOT NULL, total_tax_amount DOUBLE PRECISION NOT NULL, total_taxes JSON NOT NULL, client_attributes JSON DEFAULT \'[]\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6B71CBF419EB6921 ON quote (client_id)');
        $this->addSql('CREATE INDEX IDX_6B71CBF46A6925FE ON quote (client_invoice_country_id)');
        $this->addSql('CREATE INDEX IDX_6B71CBF432C8A3DE ON quote (organization_id)');
        $this->addSql('CREATE INDEX IDX_6B71CBF4EAC0FE63 ON quote (organization_state_id)');
        $this->addSql('CREATE INDEX IDX_6B71CBF41074AC9F ON quote (organization_country_id)');
        $this->addSql('CREATE INDEX IDX_6B71CBF42B3E0C04 ON quote (client_state_id)');
        $this->addSql('CREATE INDEX IDX_6B71CBF4697B0331 ON quote (client_country_id)');
        $this->addSql('CREATE INDEX IDX_6B71CBF43E39E362 ON quote (client_invoice_state_id)');
        $this->addSql('CREATE INDEX IDX_6B71CBF438248176 ON quote (currency_id)');
        $this->addSql('COMMENT ON COLUMN quote.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('CREATE TABLE quote_item_fee (item_id INT NOT NULL, fee_id INT DEFAULT NULL, PRIMARY KEY(item_id))');
        $this->addSql('CREATE INDEX IDX_9B66B716AB45AECA ON quote_item_fee (fee_id)');
        $this->addSql('ALTER TABLE quote_item ADD CONSTRAINT FK_8DFC7A94DB805178 FOREIGN KEY (quote_id) REFERENCES quote (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_item ADD CONSTRAINT FK_8DFC7A94B661D75 FOREIGN KEY (tax_id1) REFERENCES tax (tax_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_item ADD CONSTRAINT FK_8DFC7A94926F4CCF FOREIGN KEY (tax_id2) REFERENCES tax (tax_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_item ADD CONSTRAINT FK_8DFC7A94E5687C59 FOREIGN KEY (tax_id3) REFERENCES tax (tax_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_item_product ADD CONSTRAINT FK_6FF67DFF4584665A FOREIGN KEY (product_id) REFERENCES product (product_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_item_product ADD CONSTRAINT FK_6FF67DFF126F525E FOREIGN KEY (item_id) REFERENCES quote_item (item_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_item_service ADD CONSTRAINT FK_5D21E380ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (service_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_item_service ADD CONSTRAINT FK_5D21E38080EBDE72 FOREIGN KEY (original_service_id) REFERENCES service (service_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_item_service ADD CONSTRAINT FK_5D21E380126F525E FOREIGN KEY (item_id) REFERENCES quote_item (item_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_item_surcharge ADD CONSTRAINT FK_F59DFA2D39451620 FOREIGN KEY (service_surcharge_id) REFERENCES service_surcharge (service_surcharge_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_item_surcharge ADD CONSTRAINT FK_F59DFA2DED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (service_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_item_surcharge ADD CONSTRAINT FK_F59DFA2D126F525E FOREIGN KEY (item_id) REFERENCES quote_item (item_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_item_other ADD CONSTRAINT FK_43A8544B126F525E FOREIGN KEY (item_id) REFERENCES quote_item (item_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF419EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF46A6925FE FOREIGN KEY (client_invoice_country_id) REFERENCES country (country_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF432C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (organization_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF4EAC0FE63 FOREIGN KEY (organization_state_id) REFERENCES state (state_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF41074AC9F FOREIGN KEY (organization_country_id) REFERENCES country (country_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF42B3E0C04 FOREIGN KEY (client_state_id) REFERENCES state (state_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF4697B0331 FOREIGN KEY (client_country_id) REFERENCES country (country_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF43E39E362 FOREIGN KEY (client_invoice_state_id) REFERENCES state (state_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF438248176 FOREIGN KEY (currency_id) REFERENCES currency (currency_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_item_fee ADD CONSTRAINT FK_9B66B716AB45AECA FOREIGN KEY (fee_id) REFERENCES fee (fee_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_item_fee ADD CONSTRAINT FK_9B66B716126F525E FOREIGN KEY (item_id) REFERENCES quote_item (item_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice_item_service ADD period INT NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE quote_item_product DROP CONSTRAINT FK_6FF67DFF126F525E');
        $this->addSql('ALTER TABLE quote_item_service DROP CONSTRAINT FK_5D21E380126F525E');
        $this->addSql('ALTER TABLE quote_item_surcharge DROP CONSTRAINT FK_F59DFA2D126F525E');
        $this->addSql('ALTER TABLE quote_item_other DROP CONSTRAINT FK_43A8544B126F525E');
        $this->addSql('ALTER TABLE quote_item_fee DROP CONSTRAINT FK_9B66B716126F525E');
        $this->addSql('ALTER TABLE quote_item DROP CONSTRAINT FK_8DFC7A94DB805178');
        $this->addSql('DROP TABLE quote_item');
        $this->addSql('DROP TABLE quote_item_product');
        $this->addSql('DROP TABLE quote_item_service');
        $this->addSql('DROP TABLE quote_item_surcharge');
        $this->addSql('DROP TABLE quote_item_other');
        $this->addSql('DROP TABLE quote');
        $this->addSql('DROP TABLE quote_item_fee');
    }
}
