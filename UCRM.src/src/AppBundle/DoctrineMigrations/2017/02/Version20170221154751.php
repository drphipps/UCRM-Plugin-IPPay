<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170221154751 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO state (state_id, country_id, name, code) VALUES (51, 54, \'Alberta\', \'AB\')');
        $this->addSql('INSERT INTO state (state_id, country_id, name, code) VALUES (52, 54, \'British Columbia\', \'BC\')');
        $this->addSql('INSERT INTO state (state_id, country_id, name, code) VALUES (53, 54, \'Manitoba\', \'MB\')');
        $this->addSql('INSERT INTO state (state_id, country_id, name, code) VALUES (54, 54, \'New Brunswick\', \'NB\')');
        $this->addSql('INSERT INTO state (state_id, country_id, name, code) VALUES (55, 54, \'Newfoundland and Labrador\', \'NL\')');
        $this->addSql('INSERT INTO state (state_id, country_id, name, code) VALUES (56, 54, \'Nova Scotia\', \'NS\')');
        $this->addSql('INSERT INTO state (state_id, country_id, name, code) VALUES (57, 54, \'Ontario\', \'ON\')');
        $this->addSql('INSERT INTO state (state_id, country_id, name, code) VALUES (58, 54, \'Prince Edward Island\', \'PE\')');
        $this->addSql('INSERT INTO state (state_id, country_id, name, code) VALUES (59, 54, \'Quebec\', \'QC\')');
        $this->addSql('INSERT INTO state (state_id, country_id, name, code) VALUES (60, 54, \'Saskatchewan\', \'SK\')');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM state WHERE state_id >= 51 AND state_id <= 60');
    }
}
