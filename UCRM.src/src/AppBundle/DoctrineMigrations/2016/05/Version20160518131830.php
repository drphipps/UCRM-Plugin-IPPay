<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160518131830 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $maxStripePaymentId = $this->getPaymentStripeMaxId();

        $this->addSql('DROP SEQUENCE payment_stripe_payment_paypal_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE payment_plan_payment_plan_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql(sprintf('CREATE SEQUENCE payment_stripe_payment_stripe_id_seq INCREMENT BY 1 MINVALUE 1 START %s', $maxStripePaymentId));
        $this->addSql('CREATE TABLE payment_plan (payment_plan_id INT NOT NULL, client_id INT NOT NULL, currency_id INT NOT NULL, name VARCHAR(255) NOT NULL, provider VARCHAR(20) DEFAULT NULL, provider_plan_id VARCHAR(255) DEFAULT NULL, provider_subscription_id VARCHAR(255) DEFAULT NULL, amount INT NOT NULL, period INT NOT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, canceled_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, active BOOLEAN DEFAULT \'false\' NOT NULL, PRIMARY KEY(payment_plan_id))');
        $this->addSql('CREATE INDEX IDX_FCD9CC0919EB6921 ON payment_plan (client_id)');
        $this->addSql('CREATE INDEX IDX_FCD9CC0938248176 ON payment_plan (currency_id)');
        $this->addSql('ALTER TABLE payment_plan ADD CONSTRAINT FK_FCD9CC0919EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment_plan ADD CONSTRAINT FK_FCD9CC0938248176 FOREIGN KEY (currency_id) REFERENCES currency (currency_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE client ADD stripe_customer_id VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_stripe DROP CONSTRAINT payment_stripe_pkey');
        $this->addSql('ALTER TABLE payment_stripe ALTER client_id DROP NOT NULL');
        $this->addSql('ALTER TABLE payment_stripe ALTER request_id DROP NOT NULL');
        $this->addSql('ALTER TABLE payment_stripe ALTER source_fingerprint DROP NOT NULL');
        $this->addSql('ALTER TABLE payment_stripe RENAME COLUMN payment_paypal_id TO payment_stripe_id');
        $this->addSql('ALTER TABLE payment_stripe ADD PRIMARY KEY (payment_stripe_id)');
        $this->addSql('ALTER TABLE service ADD payment_plan_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD2F1D00C71 FOREIGN KEY (payment_plan_id) REFERENCES payment_plan (payment_plan_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E19D9AD2F1D00C71 ON service (payment_plan_id)');
        $this->addSql('INSERT INTO option (option_id, position, code, name, description, type, value, category_id, value_txt, choice_type_options) VALUES (25, 4, \'RECURRING_PAYMENTS_ENABLED\', \'Recurring payments feature\', \'This feature provides support for recurring payments. If enabled, clients will be able to subscribe to recurring payments in client zone.\', \'toggle\', \'0\', \'2\', NULL, NULL)');
        $this->addSql('INSERT INTO notification_template (template_id, subject, body, type) VALUES (10, \'Stripe subscription cancelled\', \'Dear %CLIENT_FIRST_NAME%! Your stripe subscription has been cancelled.\', 10)');
        $this->addSql('ALTER TABLE invoice_item_surcharge DROP CONSTRAINT FK_B891984339451620');
        $this->addSql('ALTER TABLE invoice_item_surcharge ADD CONSTRAINT FK_B891984339451620 FOREIGN KEY (service_surcharge_id) REFERENCES service_surcharge (service_surcharge_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE payment_plan_unsubscribe (payment_plan_id INT NOT NULL, PRIMARY KEY(payment_plan_id))');
        $this->addSql('ALTER TABLE payment_plan_unsubscribe ADD CONSTRAINT FK_1E0D92DBF1D00C71 FOREIGN KEY (payment_plan_id) REFERENCES payment_plan (payment_plan_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $maxStripePaymentId = $this->getPaymentStripeMaxId(false);

        $this->addSql('ALTER TABLE service DROP CONSTRAINT FK_E19D9AD2F1D00C71');
        $this->addSql('DROP SEQUENCE payment_plan_payment_plan_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE payment_stripe_payment_stripe_id_seq CASCADE');
        $this->addSql(sprintf('CREATE SEQUENCE payment_stripe_payment_paypal_id_seq INCREMENT BY 1 MINVALUE 1 START %s', $maxStripePaymentId));
        $this->addSql('DROP TABLE payment_plan');
        $this->addSql('ALTER TABLE client DROP stripe_customer_id');
        $this->addSql('ALTER TABLE payment_stripe DROP CONSTRAINT payment_stripe_pkey');
        $this->addSql('ALTER TABLE payment_stripe ALTER client_id SET NOT NULL');
        $this->addSql('UPDATE payment_stripe SET request_id = \'\' WHERE request_id IS NULL');
        $this->addSql('ALTER TABLE payment_stripe ALTER request_id SET NOT NULL');
        $this->addSql('ALTER TABLE payment_stripe ALTER source_fingerprint SET NOT NULL');
        $this->addSql('ALTER TABLE payment_stripe RENAME COLUMN payment_stripe_id TO payment_paypal_id');
        $this->addSql('ALTER TABLE payment_stripe ADD PRIMARY KEY (payment_paypal_id)');
        $this->addSql('DROP INDEX IDX_E19D9AD2F1D00C71');
        $this->addSql('ALTER TABLE service DROP payment_plan_id');
        $this->addSql('DELETE FROM option WHERE option_id = 25');
        $this->addSql('DELETE FROM notification_template WHERE template_id = 10');
        $this->addSql('ALTER TABLE invoice_item_surcharge DROP CONSTRAINT fk_b891984339451620');
        $this->addSql('ALTER TABLE invoice_item_surcharge ADD CONSTRAINT fk_b891984339451620 FOREIGN KEY (service_surcharge_id) REFERENCES service_surcharge (service_surcharge_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE payment_plan_unsubscribe');
    }

    /**
     * @param bool $up
     *
     * @return int
     */
    private function getPaymentStripeMaxId($up = true)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select(sprintf('MAX(ps.%s)', $up ? 'payment_paypal_id' : 'payment_stripe_id'))
            ->from('payment_stripe', 'ps')
            ->setMaxResults(1);
        $maxStripePaymentId = (int) $qb->execute()->fetchColumn();

        return $maxStripePaymentId + 1;
    }
}
