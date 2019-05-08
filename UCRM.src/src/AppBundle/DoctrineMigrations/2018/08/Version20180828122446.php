<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180828122446 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE service_invoice (service_service_id INT NOT NULL, invoice_invoice_id INT NOT NULL, PRIMARY KEY(service_service_id, invoice_invoice_id))');
        $this->addSql('CREATE INDEX IDX_5FE145D87B0D9052 ON service_invoice (service_service_id)');
        $this->addSql('CREATE INDEX IDX_5FE145D8D0558163 ON service_invoice (invoice_invoice_id)');
        $this->addSql('ALTER TABLE service_invoice ADD CONSTRAINT FK_5FE145D87B0D9052 FOREIGN KEY (service_service_id) REFERENCES service (service_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_invoice ADD CONSTRAINT FK_5FE145D8D0558163 FOREIGN KEY (invoice_invoice_id) REFERENCES invoice (invoice_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE service_invoice');
    }
}
