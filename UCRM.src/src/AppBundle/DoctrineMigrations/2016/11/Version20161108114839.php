<?php

namespace AppBundle\Migrations;

use AppBundle\Entity\PaymentAuthorizeNet;
use AppBundle\Entity\PaymentCustom;
use AppBundle\Entity\PaymentPayPal;
use AppBundle\Entity\PaymentProvider;
use AppBundle\Entity\PaymentStripe;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20161108114839 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE payment_provider (provider_id SERIAL NOT NULL, name VARCHAR(30) NOT NULL, payment_details_class VARCHAR(100) NOT NULL, PRIMARY KEY(provider_id))');
        $this->addSql('CREATE TABLE payment_custom (payment_custom_id SERIAL NOT NULL, provider_name VARCHAR(255) NOT NULL, provider_payment_id VARCHAR(255) NOT NULL, provider_payment_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, amount DOUBLE PRECISION NOT NULL, currency VARCHAR(5) NOT NULL, PRIMARY KEY(payment_custom_id))');
        $this->addSql('COMMENT ON COLUMN payment_custom.provider_payment_time IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE payment ADD provider_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD payment_details_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DA53A8AA FOREIGN KEY (provider_id) REFERENCES payment_provider (provider_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6D28840DA53A8AA ON payment (provider_id)');

        $query = 'INSERT INTO payment_provider (provider_id, name, payment_details_class) VALUES (?, ?, ?)';

        $this->addSql(
            $query,
            [
                PaymentProvider::ID_CUSTOM,
                'Custom payment provider',
                PaymentCustom::class,
            ]
        );

        $this->addSql(
            $query,
            [
                PaymentProvider::ID_PAYPAL,
                'PayPal',
                PaymentPayPal::class,
            ]
        );

        $this->addSql(
            $query,
            [
                PaymentProvider::ID_STRIPE,
                'Stripe',
                PaymentStripe::class,
            ]
        );

        $this->addSql(
            $query,
            [
                PaymentProvider::ID_AUTHORIZE_NET,
                'Authorize.Net',
                PaymentAuthorizeNet::class,
            ]
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment DROP CONSTRAINT FK_6D28840DA53A8AA');
        $this->addSql('DROP TABLE payment_provider');
        $this->addSql('DROP TABLE payment_custom');
        $this->addSql('DROP INDEX IDX_6D28840DA53A8AA');
        $this->addSql('ALTER TABLE payment DROP provider_id');
        $this->addSql('ALTER TABLE payment DROP payment_details_id');
    }
}
