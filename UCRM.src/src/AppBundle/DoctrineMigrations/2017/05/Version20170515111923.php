<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170515111923 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service ALTER suspended_from TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN service.suspended_from IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN service_device.first_seen IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN service_device.last_seen IS \'(DC2Type:datetime_utc)\'');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service ALTER suspended_from TYPE DATE');
    }
}
