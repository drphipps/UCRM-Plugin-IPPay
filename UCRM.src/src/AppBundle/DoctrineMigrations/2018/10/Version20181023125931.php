<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181023125931 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql('ALTER TABLE webhook_address ADD verify_ssl_certificate BOOLEAN DEFAULT \'true\' NOT NULL');
        $this->addSql('ALTER TABLE webhook_event_request ADD verify_ssl_certificate BOOLEAN DEFAULT \'true\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql('ALTER TABLE webhook_address DROP verify_ssl_certificate');
        $this->addSql('ALTER TABLE webhook_event_request DROP verify_ssl_certificate');
    }
}
