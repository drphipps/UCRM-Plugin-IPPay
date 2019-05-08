<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190114114009 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization ADD anet_sandbox_signature_key VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD anet_live_signature_key VARCHAR(128) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization DROP anet_sandbox_signature_key');
        $this->addSql('ALTER TABLE organization DROP anet_live_signature_key');
    }
}
