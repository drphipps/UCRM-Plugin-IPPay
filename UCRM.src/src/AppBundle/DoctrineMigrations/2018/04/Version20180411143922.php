<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180411143922 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE ticket_job (ticket_id INT NOT NULL, job_id INT NOT NULL, PRIMARY KEY(ticket_id, job_id))');
        $this->addSql('CREATE INDEX IDX_F8A13BD4700047D2 ON ticket_job (ticket_id)');
        $this->addSql('CREATE INDEX IDX_F8A13BD4BE04EA9 ON ticket_job (job_id)');
        $this->addSql('ALTER TABLE ticket_job ADD CONSTRAINT FK_F8A13BD4700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_job ADD CONSTRAINT FK_F8A13BD4BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE ticket_job');
    }
}
