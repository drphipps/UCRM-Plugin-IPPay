<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170328150555 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE ucrm_version (id SERIAL NOT NULL, channel VARCHAR(20) NOT NULL, version VARCHAR(20) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FFEC1E33A2F98E47 ON ucrm_version (channel)');
        $this->addSql('INSERT INTO ucrm_version (channel, version) VALUES (\'stable\', \'2.2.2\'), (\'beta\', \'2.3.0-beta1\')');
        $this->addSql('DELETE FROM general WHERE code = \'crm_latest_version\'');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE ucrm_version');
        $this->addSql('INSERT INTO general (code, value) VALUES (\'crm_latest_version\', \'0.0.0\')');
    }
}
