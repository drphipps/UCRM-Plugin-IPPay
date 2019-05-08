<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170220132322 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log ALTER script TYPE TEXT');
        $this->addSql('ALTER TABLE service_device_log ALTER script TYPE TEXT');
        $this->addSql('ALTER TABLE device_log ALTER script TYPE TEXT');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device_log ALTER script TYPE VARCHAR(100)');
        $this->addSql('ALTER TABLE email_log ALTER script TYPE VARCHAR(100)');
        $this->addSql('ALTER TABLE service_device_log ALTER script TYPE VARCHAR(100)');
    }
}
