<?php

namespace AppBundle\Migrations;

use AppBundle\Entity\PaymentIpPay;
use AppBundle\Entity\PaymentProvider;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170509112347 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization ADD ip_pay_sandbox_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD ip_pay_live_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD ip_pay_sandbox_terminal_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD ip_pay_live_terminal_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD ip_pay_sandbox_merchant_currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD ip_pay_live_merchant_currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C9D477420 FOREIGN KEY (ip_pay_sandbox_merchant_currency_id) REFERENCES currency (currency_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637CC33610DE FOREIGN KEY (ip_pay_live_merchant_currency_id) REFERENCES currency (currency_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C1EE637C9D477420 ON organization (ip_pay_sandbox_merchant_currency_id)');
        $this->addSql('CREATE INDEX IDX_C1EE637CC33610DE ON organization (ip_pay_live_merchant_currency_id)');
        $this->addSql('CREATE TABLE payment_ippay (payment_paypal_id SERIAL NOT NULL, transaction_id VARCHAR(18) NOT NULL, currency VARCHAR(5) NOT NULL, PRIMARY KEY(payment_paypal_id))');

        $this->addSql(
            'INSERT INTO payment_provider (provider_id, name, payment_details_class) VALUES (?, ?, ?)',
            [
                PaymentProvider::ID_IPPAY,
                'IPPay',
                PaymentIpPay::class,
            ]
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization DROP ip_pay_sandbox_url');
        $this->addSql('ALTER TABLE organization DROP ip_pay_live_url');
        $this->addSql('ALTER TABLE organization DROP ip_pay_sandbox_terminal_id');
        $this->addSql('ALTER TABLE organization DROP ip_pay_live_terminal_id');
        $this->addSql('ALTER TABLE organization DROP CONSTRAINT FK_C1EE637C9D477420');
        $this->addSql('ALTER TABLE organization DROP CONSTRAINT FK_C1EE637CC33610DE');
        $this->addSql('DROP INDEX IDX_C1EE637C9D477420');
        $this->addSql('DROP INDEX IDX_C1EE637CC33610DE');
        $this->addSql('ALTER TABLE organization DROP ip_pay_sandbox_merchant_currency_id');
        $this->addSql('ALTER TABLE organization DROP ip_pay_live_merchant_currency_id');
        $this->addSql('DROP TABLE payment_ippay');
    }
}
