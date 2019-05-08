<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170621123039 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE job_task (id SERIAL NOT NULL, job_id INT NOT NULL, label VARCHAR(255) NOT NULL, closed BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D5B2AD64BE04EA9 ON job_task (job_id)');
        $this->addSql('ALTER TABLE job_task ADD CONSTRAINT FK_D5B2AD64BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE job_task');
    }
}
