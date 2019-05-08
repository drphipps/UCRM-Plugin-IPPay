<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180301095129 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE payment_receipt_template (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, error_notification_sent BOOLEAN DEFAULT \'false\' NOT NULL, official_name VARCHAR(100) DEFAULT NULL, is_valid BOOLEAN DEFAULT \'true\' NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_82AEF0835E237E06 ON payment_receipt_template (name)');
        $this->addSql('COMMENT ON COLUMN payment_receipt_template.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN payment_receipt_template.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER SEQUENCE payment_receipt_template_id_seq RESTART WITH 1000');
        $this->addSql('INSERT INTO payment_receipt_template (id, name, created_date, official_name) VALUES (1, \'Default\', now(), \'default\')');
        $this->addSql('ALTER TABLE organization ADD payment_receipt_template_id INT NULL');
        $this->addSql('UPDATE organization SET payment_receipt_template_id = 1');
        $this->addSql('ALTER TABLE organization ALTER payment_receipt_template_id SET NOT NULL');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637CA0E00F39 FOREIGN KEY (payment_receipt_template_id) REFERENCES payment_receipt_template (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C1EE637CA0E00F39 ON organization (payment_receipt_template_id)');
        $this->addSql(
            'INSERT INTO option (code, value) VALUES (?, ?)',
            [
                'BACKUP_INCLUDE_PAYMENT_RECEIPT_TEMPLATES',
                1,
            ]
        );
        $this->addSql('ALTER TABLE payment ADD payment_receipt_template_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DA0E00F39 FOREIGN KEY (payment_receipt_template_id) REFERENCES payment_receipt_template (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6D28840DA0E00F39 ON payment (payment_receipt_template_id)');
        $this->addSql('UPDATE payment SET payment_receipt_template_id = 1 WHERE client_id IS NOT NULL');
        $this->addSql('INSERT INTO user_group_permission (group_id, module_name, permission) VALUES (1, \'AppBundle\Controller\PaymentReceiptTemplateController\', \'edit\')');
        $this->addSql('UPDATE notification_template SET body = body || \' <p>%PAYMENT_RECEIPT%</p>\' WHERE template_id = 11 AND body NOT LIKE  \'%%PAYMENT_RECEIPT%%\'');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment DROP CONSTRAINT FK_6D28840DA0E00F39');
        $this->addSql('DROP INDEX IDX_6D28840DA0E00F39');
        $this->addSql('ALTER TABLE payment DROP payment_receipt_template_id');
        $this->addSql('ALTER TABLE organization DROP CONSTRAINT FK_C1EE637CA0E00F39');
        $this->addSql('DROP TABLE payment_receipt_template');
        $this->addSql('DROP INDEX IDX_C1EE637CA0E00F39');
        $this->addSql('ALTER TABLE organization DROP payment_receipt_template_id');
        $this->addSql(
            'DELETE FROM option WHERE code = ?',
            [
                'BACKUP_INCLUDE_PAYMENT_RECEIPT_TEMPLATES',
            ]
        );
        $this->addSql('DELETE FROM user_group_permission WHERE module_name=\'AppBundle\Controller\PaymentReceiptTemplateController\'');
    }
}
