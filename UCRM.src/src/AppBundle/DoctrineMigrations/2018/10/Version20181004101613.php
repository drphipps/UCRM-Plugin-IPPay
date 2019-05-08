<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181004101613 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_invoice DROP CONSTRAINT FK_5FE145D8D0558163');
        $this->addSql('ALTER TABLE service_invoice DROP CONSTRAINT FK_5FE145D87B0D9052');
        $this->addSql('ALTER TABLE service_invoice ADD CONSTRAINT FK_5FE145D8D0558163 FOREIGN KEY (invoice_invoice_id) REFERENCES invoice (invoice_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_invoice ADD CONSTRAINT FK_5FE145D87B0D9052 FOREIGN KEY (service_service_id) REFERENCES service (service_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_invoice DROP CONSTRAINT fk_5fe145d87b0d9052');
        $this->addSql('ALTER TABLE service_invoice DROP CONSTRAINT fk_5fe145d8d0558163');
        $this->addSql('ALTER TABLE service_invoice ADD CONSTRAINT fk_5fe145d87b0d9052 FOREIGN KEY (service_service_id) REFERENCES service (service_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_invoice ADD CONSTRAINT fk_5fe145d8d0558163 FOREIGN KEY (invoice_invoice_id) REFERENCES invoice (invoice_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
