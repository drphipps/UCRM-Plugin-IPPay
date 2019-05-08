<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170515113822 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('
          INSERT INTO service_stop_reason (reason_id, name)
          SELECT 1, \'Payments overdue\'
          WHERE NOT EXISTS (
            SELECT reason_id FROM service_stop_reason WHERE reason_id = 1
          )
        ');
        $this->addSql('
          INSERT INTO service_stop_reason (reason_id, name)
          SELECT 2, \'Service not yet active\'
          WHERE NOT EXISTS (
            SELECT reason_id FROM service_stop_reason WHERE reason_id = 2
          )
        ');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
    }
}
