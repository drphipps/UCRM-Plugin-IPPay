<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170523102255 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE suspension_period (id SERIAL NOT NULL, service_id INT DEFAULT NULL, since DATE NOT NULL, until DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D9611E09ED5CA9E6 ON suspension_period (service_id)');
        $this->addSql('ALTER TABLE suspension_period ADD CONSTRAINT FK_D9611E09ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (service_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('INSERT INTO suspension_period (service_id, since) SELECT service_id, suspended_from FROM service WHERE suspended_from IS NOT NULL AND status = 3');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE suspension_period');
    }
}
