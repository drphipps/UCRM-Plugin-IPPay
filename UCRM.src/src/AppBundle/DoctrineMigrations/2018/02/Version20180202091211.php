<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180202091211 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE app_key ADD plugin_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_key ADD CONSTRAINT FK_A4E2186EEC942BCF FOREIGN KEY (plugin_id) REFERENCES plugin (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A4E2186EEC942BCF ON app_key (plugin_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE app_key DROP CONSTRAINT FK_A4E2186EEC942BCF');
        $this->addSql('DROP INDEX UNIQ_A4E2186EEC942BCF');
        $this->addSql('ALTER TABLE app_key DROP plugin_id');
    }
}
