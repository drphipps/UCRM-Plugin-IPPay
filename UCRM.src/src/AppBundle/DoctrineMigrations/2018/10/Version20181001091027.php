<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181001091027 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE app_key DROP CONSTRAINT FK_A4E2186EEC942BCF');
        $this->addSql('ALTER TABLE app_key ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE app_key ALTER key DROP NOT NULL');
        $this->addSql('COMMENT ON COLUMN app_key.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE app_key ADD CONSTRAINT FK_A4E2186EEC942BCF FOREIGN KEY (plugin_id) REFERENCES plugin (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_activity ADD app_key_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_activity ADD CONSTRAINT FK_291DF5DBE81738B3 FOREIGN KEY (app_key_id) REFERENCES app_key (key_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_291DF5DBE81738B3 ON ticket_activity (app_key_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE app_key DROP CONSTRAINT fk_a4e2186eec942bcf');
        $this->addSql('ALTER TABLE app_key DROP deleted_at');
        $this->addSql('ALTER TABLE app_key ALTER key SET NOT NULL');
        $this->addSql('ALTER TABLE app_key ADD CONSTRAINT fk_a4e2186eec942bcf FOREIGN KEY (plugin_id) REFERENCES plugin (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_activity DROP CONSTRAINT FK_291DF5DBE81738B3');
        $this->addSql('DROP INDEX IDX_291DF5DBE81738B3');
        $this->addSql('ALTER TABLE ticket_activity DROP app_key_id');
    }
}
