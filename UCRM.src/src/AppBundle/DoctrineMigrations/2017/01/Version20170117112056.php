<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170117112056 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client ALTER user_ident_int TYPE BIGINT');
        $this->addSql('DELETE FROM device_interface WHERE device_id IS NULL');
        $this->addSql('ALTER TABLE device_interface ALTER device_id SET NOT NULL');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client ALTER user_ident_int TYPE INT');
        $this->addSql('ALTER TABLE device_interface ALTER device_id DROP NOT NULL');
    }
}
