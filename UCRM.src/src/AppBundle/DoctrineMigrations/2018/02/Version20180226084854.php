<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180226084854 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE user_personalization ADD client_show_client_log BOOLEAN DEFAULT \'true\' NOT NULL');
        $this->addSql('ALTER TABLE user_personalization ADD client_show_email_log BOOLEAN DEFAULT \'true\' NOT NULL');
        $this->addSql('ALTER TABLE user_personalization ADD client_show_system_log BOOLEAN DEFAULT \'true\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE user_personalization DROP client_show_client_log');
        $this->addSql('ALTER TABLE user_personalization DROP client_show_email_log');
        $this->addSql('ALTER TABLE user_personalization DROP client_show_system_log');
    }
}
