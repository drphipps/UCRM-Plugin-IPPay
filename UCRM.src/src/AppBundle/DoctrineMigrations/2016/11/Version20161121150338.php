<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20161121150338 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device_outage DROP CONSTRAINT FK_106EFCE394A4C7D4');
        $this->addSql('ALTER TABLE device_outage ADD CONSTRAINT FK_106EFCE394A4C7D4 FOREIGN KEY (device_id) REFERENCES device (device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device_outage DROP CONSTRAINT fk_106efce394a4c7d4');
        $this->addSql('ALTER TABLE device_outage ADD CONSTRAINT fk_106efce394a4c7d4 FOREIGN KEY (device_id) REFERENCES device (device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
