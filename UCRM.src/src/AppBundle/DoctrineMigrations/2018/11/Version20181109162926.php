<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181109162926 extends AbstractMigration
{
    /**
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE webhook_event ADD extra_data JSON DEFAULT \'[]\' NOT NULL');
        $this->addSql('ALTER TABLE ticket_status_change ADD previous_status SMALLINT NULL');

        $this->addSql('ALTER TABLE webhook_event_request ALTER request_body TYPE TEXT');
    }

    /**
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE webhook_event_request ALTER request_body TYPE VARCHAR(255)');

        $this->addSql('ALTER TABLE ticket_status_change DROP previous_status');
        $this->addSql('ALTER TABLE webhook_event DROP extra_data');
    }
}
