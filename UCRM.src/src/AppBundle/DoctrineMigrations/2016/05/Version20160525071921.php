<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160525071921 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client ADD paypal_customer_id VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_paypal ADD type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE payment_paypal ADD amount DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE payment_paypal ADD currency VARCHAR(5) NOT NULL');
        $this->addSql('ALTER TABLE payment_paypal ALTER intent DROP NOT NULL');
        $this->addSql('ALTER TABLE payment_plan ADD status VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_plan RENAME COLUMN amount TO amount_in_cents');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client DROP paypal_customer_id');
        $this->addSql('ALTER TABLE payment_paypal DROP type');
        $this->addSql('ALTER TABLE payment_paypal DROP amount');
        $this->addSql('ALTER TABLE payment_paypal DROP currency');
        $this->addSql('ALTER TABLE payment_paypal ALTER intent SET NOT NULL');
        $this->addSql('ALTER TABLE payment_plan DROP status');
        $this->addSql('ALTER TABLE payment_plan RENAME COLUMN amount_in_cents TO amount');
    }
}
