<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170911115621 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE job_task ADD sequence INT NULL');
        $this->addSql('UPDATE job_task SET sequence = (SELECT COUNT(t.id) FROM job_task t WHERE t.job_id = job_task.job_id AND t.id < job_task.id)');
        $this->addSql('ALTER TABLE job_task ALTER sequence SET NOT NULL');
        $this->addSql('CREATE INDEX IDX_D5B2AD64BE04EA95286D72B ON job_task (job_id, sequence)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX IDX_D5B2AD64BE04EA95286D72B');
        $this->addSql('ALTER TABLE job_task DROP sequence');
    }
}
