<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170428090332 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device ADD last_successful_synchronization TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN device.last_successful_synchronization IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE service_device ADD last_successful_synchronization TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN service_device.last_successful_synchronization IS \'(DC2Type:datetime_utc)\'');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device DROP last_successful_synchronization');
        $this->addSql('ALTER TABLE service_device DROP last_successful_synchronization');
    }
}
