<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20161101145049 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tariff ALTER download_speed TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE tariff ALTER download_speed DROP DEFAULT');
        $this->addSql('ALTER TABLE tariff ALTER upload_speed TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE tariff ALTER upload_speed DROP DEFAULT');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tariff ALTER download_speed TYPE INT');
        $this->addSql('ALTER TABLE tariff ALTER download_speed DROP DEFAULT');
        $this->addSql('ALTER TABLE tariff ALTER upload_speed TYPE INT');
        $this->addSql('ALTER TABLE tariff ALTER upload_speed DROP DEFAULT');
    }
}
