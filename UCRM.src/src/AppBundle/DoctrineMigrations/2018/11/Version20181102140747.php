<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181102140747 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE client_error_summary (id UUID NOT NULL, erroneous_client_count INT DEFAULT 0 NOT NULL, missing_taxes JSON DEFAULT \'[]\' NOT NULL, missing_service_plans JSON DEFAULT \'[]\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE client_error_summary_item (id UUID NOT NULL, client_error_summary_id UUID NOT NULL, hash VARCHAR(40) NOT NULL, type VARCHAR(255) NOT NULL, count INT DEFAULT 0 NOT NULL, line_numbers JSON DEFAULT \'[]\' NOT NULL, error JSON DEFAULT \'[]\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4FA00D46ED4D0722 ON client_error_summary_item (client_error_summary_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4FA00D46ED4D0722D1B862B8 ON client_error_summary_item (client_error_summary_id, hash)');
        $this->addSql('ALTER TABLE client_error_summary_item ADD CONSTRAINT FK_4FA00D46ED4D0722 FOREIGN KEY (client_error_summary_id) REFERENCES client_error_summary (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE client_import ADD error_summary_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE client_import ADD CONSTRAINT FK_C8E885AEC98482D9 FOREIGN KEY (error_summary_id) REFERENCES client_error_summary (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C8E885AEC98482D9 ON client_import (error_summary_id)');
        $this->addSql('ALTER TABLE client_import_item ADD has_errors BOOLEAN DEFAULT \'false\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client_import DROP CONSTRAINT FK_C8E885AEC98482D9');
        $this->addSql('ALTER TABLE client_error_summary_item DROP CONSTRAINT FK_4FA00D46ED4D0722');
        $this->addSql('DROP TABLE client_error_summary');
        $this->addSql('DROP TABLE client_error_summary_item');
        $this->addSql('DROP INDEX UNIQ_C8E885AEC98482D9');
        $this->addSql('ALTER TABLE client_import DROP error_summary_id');
        $this->addSql('ALTER TABLE client_import_item DROP has_errors');
    }
}
