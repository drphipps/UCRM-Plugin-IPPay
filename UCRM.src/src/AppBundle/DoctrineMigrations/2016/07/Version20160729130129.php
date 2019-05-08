<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160729130129 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service RENAME COLUMN invoicing_from TO invoicing_start');
        $this->addSql('ALTER TABLE service ADD invoicing_period_start_day INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE service ADD invoicing_last_period_start DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD invoicing_last_period_end DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD next_invoicing_day_adjustment INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE service ADD invoicing_prorated_separately BOOLEAN DEFAULT \'true\' NOT NULL');
        $this->addSql('ALTER TABLE service DROP prev_invoicing_period_end');
        $this->addSql('ALTER TABLE service DROP prev_invoicing_period_start');
        $this->addSql('ALTER TABLE service ALTER invoicing_separately SET DEFAULT \'false\'');
        $this->addSql('UPDATE service SET invoicing_separately = \'false\' WHERE invoicing_separately IS NULL');
        $this->addSql('ALTER TABLE service ALTER invoicing_separately SET NOT NULL');
        $this->addSql('ALTER TABLE service ADD send_emails_automatically BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE service ADD use_credit_automatically BOOLEAN DEFAULT \'true\' NOT NULL');
        $this->addSql('ALTER TABLE service DROP seasonal_active_from');
        $this->addSql('ALTER TABLE service DROP seasonal_active_to');
        $this->addSql('ALTER TABLE service DROP seasonal_repeat');
        $this->addSql('UPDATE option SET position = position + 1 WHERE category_id = 2 AND position >= 1');
        $this->addSql('INSERT INTO option (option_id, category_id, position, code, name, description, type, value, choice_type_options) VALUES (34, 2, 1, \'BILLING_CYCLE_TYPE\', \'Billing cycle type\', \'Determines if pro-rated periods should always use 30 days in month, or real day count to calculate quantity.\', \'choice\', \'1\', \'{"choices":{"0":"Real day count","1":"30 days in month"}}\')');
        $this->addSql('UPDATE option SET name = \'Approve and send emails automatically\', description = \'If checked, invoice drafts are automatically approved and sent by email right after generating.\' WHERE option_id = 6'); // SEND_INVOICE_BY_EMAIL
        $this->addSql('ALTER TABLE invoice_item DROP CONSTRAINT FK_1DDE477B2989F1FD');
        $this->addSql('ALTER TABLE invoice_item ADD CONSTRAINT FK_1DDE477B2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (invoice_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service RENAME COLUMN invoicing_start TO invoicing_from');
        $this->addSql('ALTER TABLE service ADD prev_invoicing_period_end DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD prev_invoicing_period_start DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE service DROP invoicing_period_start_day');
        $this->addSql('ALTER TABLE service DROP invoicing_last_period_start');
        $this->addSql('ALTER TABLE service DROP invoicing_last_period_end');
        $this->addSql('ALTER TABLE service DROP next_invoicing_day_adjustment');
        $this->addSql('ALTER TABLE service DROP invoicing_prorated_separately');
        $this->addSql('ALTER TABLE service ALTER invoicing_separately DROP DEFAULT');
        $this->addSql('ALTER TABLE service ALTER invoicing_separately DROP NOT NULL');
        $this->addSql('ALTER TABLE service DROP send_emails_automatically');
        $this->addSql('ALTER TABLE service DROP use_credit_automatically');
        $this->addSql('ALTER TABLE service ADD seasonal_active_from DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD seasonal_active_to DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD seasonal_repeat BOOLEAN DEFAULT NULL');
        $this->addSql('DELETE FROM option WHERE option_id = 34');
        $this->addSql('UPDATE option SET position = position - 1 WHERE category_id = 2 AND position > 1');
        $this->addSql('UPDATE option SET name = \'Send invoice by email\', description = \'If checked, the approved invoices are sent by email (helpful for batch recurring invoicing) Still, you can change this manually invoice by invoice.\' WHERE option_id = 6'); // SEND_INVOICE_BY_EMAIL
        $this->addSql('ALTER TABLE invoice_item DROP CONSTRAINT fk_1dde477b2989f1fd');
        $this->addSql('ALTER TABLE invoice_item ADD CONSTRAINT fk_1dde477b2989f1fd FOREIGN KEY (invoice_id) REFERENCES invoice (invoice_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
