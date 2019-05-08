<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181127132615 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization ADD receipt_number_prefix VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD receipt_number_length INT DEFAULT 6 NOT NULL');
        $this->addSql('ALTER TABLE organization ADD receipt_init_number INT DEFAULT 1');
        $this->addSql('ALTER TABLE payment ADD receipt_number VARCHAR(60) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD organization_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (organization_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6D28840D32C8A3DE ON payment (organization_id)');
        $this->addSql('CREATE UNIQUE INDEX receipt_number_unique ON payment (receipt_number, organization_id)');
        $this->addSql('ALTER TABLE payment ADD send_receipt BOOLEAN DEFAULT \'false\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization DROP receipt_number_prefix');
        $this->addSql('ALTER TABLE organization DROP receipt_number_length');
        $this->addSql('ALTER TABLE organization DROP receipt_init_number');
        $this->addSql('ALTER TABLE payment DROP receipt_number');
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT FK_6D28840D32C8A3DE');
        $this->addSql('DROP INDEX IDX_6D28840D32C8A3DE');
        $this->addSql('DROP INDEX receipt_number_unique');
        $this->addSql('ALTER TABLE payment DROP organization_id');
        $this->addSql('ALTER TABLE payment DROP send_receipt');
    }
}
