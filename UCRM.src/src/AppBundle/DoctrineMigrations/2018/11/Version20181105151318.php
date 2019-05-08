<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181105151318 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client_import DROP CONSTRAINT fk_c8e885aec98482d9');
        $this->addSql('DROP INDEX uniq_c8e885aec98482d9');
        $this->addSql('ALTER TABLE client_import DROP error_summary_id');
        $this->addSql('ALTER TABLE client_import_item ADD can_import BOOLEAN DEFAULT \'true\' NOT NULL');
        $this->addSql('ALTER TABLE client_error_summary ADD import_id UUID NOT NULL');
        $this->addSql('ALTER TABLE client_error_summary ADD CONSTRAINT FK_24DA0D7BB6A263D9 FOREIGN KEY (import_id) REFERENCES client_import (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_24DA0D7BB6A263D9 ON client_error_summary (import_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client_error_summary DROP CONSTRAINT FK_24DA0D7BB6A263D9');
        $this->addSql('DROP INDEX UNIQ_24DA0D7BB6A263D9');
        $this->addSql('ALTER TABLE client_error_summary DROP import_id');
        $this->addSql('ALTER TABLE client_import ADD error_summary_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE client_import ADD CONSTRAINT fk_c8e885aec98482d9 FOREIGN KEY (error_summary_id) REFERENCES client_error_summary (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_c8e885aec98482d9 ON client_import (error_summary_id)');
        $this->addSql('ALTER TABLE client_import_item DROP can_import');
    }
}
