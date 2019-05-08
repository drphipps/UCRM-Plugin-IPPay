<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170710113434 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO state (state_id, country_id, name, code) VALUES (61, 54, \'Nunavut\', \'NU\')');
        $this->addSql('INSERT INTO state (state_id, country_id, name, code) VALUES (62, 54, \'Northwest Territories\', \'NT\')');
        $this->addSql('INSERT INTO state (state_id, country_id, name, code) VALUES (63, 54, \'Yukon\', \'YT\')');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM state WHERE state_id >= 61 AND state_id <= 63');
    }
}
