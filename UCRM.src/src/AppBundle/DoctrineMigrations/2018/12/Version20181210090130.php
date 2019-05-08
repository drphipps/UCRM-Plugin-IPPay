<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181210090130 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ONLY invoice DROP CONSTRAINT IF EXISTS invoice_number_organization_id_key');

        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT invoice_number_organization_proforma_id_key UNIQUE (invoice_number, organization_id, is_proforma)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ONLY invoice DROP CONSTRAINT invoice_number_organization_proforma_id_key');

        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT invoice_number_organization_id_key UNIQUE (invoice_number, organization_id)');
    }
}
