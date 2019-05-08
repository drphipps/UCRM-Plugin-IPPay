<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160608150142 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment ADD currency_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D38248176 FOREIGN KEY (currency_id) REFERENCES currency (currency_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6D28840D38248176 ON payment (currency_id)');
        $this->addSql('CREATE SEQUENCE payment_anet_payment_anet_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE payment_anet (payment_anet_id INT NOT NULL, organization_id INT NOT NULL, client_id INT NOT NULL, anet_id VARCHAR(255) NOT NULL, amount DOUBLE PRECISION NOT NULL, currency VARCHAR(5) NOT NULL, PRIMARY KEY(payment_anet_id))');
        $this->addSql('CREATE INDEX IDX_8EF614E332C8A3DE ON payment_anet (organization_id)');
        $this->addSql('CREATE INDEX IDX_8EF614E319EB6921 ON payment_anet (client_id)');
        $this->addSql('ALTER TABLE payment_anet ADD CONSTRAINT FK_8EF614E332C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (organization_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment_anet ADD CONSTRAINT FK_8EF614E319EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE client ADD anet_customer_profile_id VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD anet_login_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD anet_transaction_key VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD anet_hash VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice ADD template_include_bank_account BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE invoice ADD template_include_tax_information BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE organization ADD invoice_template_include_bank_account BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE organization ADD invoice_template_include_tax_information BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('UPDATE payment SET currency_id = (SELECT o.currency_id FROM client c JOIN organization o ON c.organization_id = o.organization_id LIMIT 1) WHERE currency_id IS NULL AND client_id IS NOT NULL;');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment DROP CONSTRAINT FK_6D28840D38248176');
        $this->addSql('DROP INDEX IDX_6D28840D38248176');
        $this->addSql('ALTER TABLE payment DROP currency_id');
        $this->addSql('DROP SEQUENCE payment_anet_payment_anet_id_seq CASCADE');
        $this->addSql('DROP TABLE payment_anet');
        $this->addSql('ALTER TABLE client DROP anet_customer_profile_id');
        $this->addSql('ALTER TABLE organization DROP anet_login_id');
        $this->addSql('ALTER TABLE organization DROP anet_transaction_key');
        $this->addSql('ALTER TABLE organization DROP anet_hash');
        $this->addSql('ALTER TABLE invoice DROP template_include_bank_account');
        $this->addSql('ALTER TABLE invoice DROP template_include_tax_information');
        $this->addSql('ALTER TABLE organization DROP invoice_template_include_bank_account');
        $this->addSql('ALTER TABLE organization DROP invoice_template_include_tax_information');
    }
}
