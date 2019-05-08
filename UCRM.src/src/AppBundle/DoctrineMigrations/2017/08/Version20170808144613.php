<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use AppBundle\Entity\PaymentMercadoPago;
use AppBundle\Entity\PaymentProvider;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170808144613 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization ADD mercado_pago_client_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD mercado_pago_client_secret VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE TABLE payment_mercado_pago (id SERIAL NOT NULL, organization_id INT NOT NULL, client_id INT NOT NULL, mercado_pago_id VARCHAR(255) NOT NULL, amount DOUBLE PRECISION NOT NULL, currency VARCHAR(3) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E8CB634932C8A3DE ON payment_mercado_pago (organization_id)');
        $this->addSql('CREATE INDEX IDX_E8CB634919EB6921 ON payment_mercado_pago (client_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E8CB63495CE4BC68 ON payment_mercado_pago (mercado_pago_id)');
        $this->addSql('ALTER TABLE payment_mercado_pago ADD CONSTRAINT FK_E8CB634932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (organization_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment_mercado_pago ADD CONSTRAINT FK_E8CB634919EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql(
            'INSERT INTO payment_provider (provider_id, name, payment_details_class) VALUES (?, ?, ?)',
            [
                PaymentProvider::ID_MERCADO_PAGO,
                'MercadoPago',
                PaymentMercadoPago::class,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization DROP mercado_pago_client_id');
        $this->addSql('ALTER TABLE organization DROP mercado_pago_client_secret');
        $this->addSql('DROP TABLE payment_mercado_pago');
        $this->addSql('DELETE FROM payment_provider WHERE provider_id = ?', PaymentProvider::ID_MERCADO_PAGO);
    }
}
