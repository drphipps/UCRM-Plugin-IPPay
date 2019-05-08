<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160817110349 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment ADD receipt_sent_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN payment.receipt_sent_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('INSERT INTO notification_template (template_id, subject, body, type) VALUES (11, \'Payment receipt\', \'<p>Dear %CLIENT_FIRST_NAME%! We have received your payment, you can find the receipt below.</p>\', 11)');
        $this->addSql('UPDATE option SET type = \'toggle\' WHERE code = \'ERROR_REPORTING\'');
        $this->addSql('UPDATE option SET position = 7 WHERE code = \'RECURRING_PAYMENTS_ENABLED\'');
        $this->addSql('UPDATE option SET position = 6 WHERE code = \'SUPPORT_EMAIL_ADDRESS\'');
        $this->addSql('UPDATE option SET position = position + 1 WHERE category_id = 4 AND position > 0');
        $this->addSql('UPDATE option SET position = 1 WHERE code = \'STOP_SERVICE_DUE\'');
        $this->addSql('INSERT INTO option (option_id, category_id, position, code, name, description, type, value, choice_type_options) VALUES (35, 2, 8, \'SEND_PAYMENT_RECEIPTS\', \'Send payment receipts automatically\', \'If turned on, payment receipt emails will be send automatically after receiving online payments.\', \'toggle\', \'0\', null)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment DROP receipt_sent_date');
        $this->addSql('DELETE FROM notification_template WHERE template_id = 11');
        $this->addSql('UPDATE option SET type = \'bool\' WHERE code = \'ERROR_REPORTING\'');
        $this->addSql('UPDATE option SET position = 6 WHERE code = \'RECURRING_PAYMENTS_ENABLED\'');
        $this->addSql('UPDATE option SET position = 5 WHERE code = \'SUPPORT_EMAIL_ADDRESS\'');
        $this->addSql('UPDATE option SET position = position - 1 WHERE category_id = 4 AND position > 0');
        $this->addSql('DELETE FROM option WHERE code = \'SEND_PAYMENT_RECEIPTS\'');
    }
}
