<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171121114059 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket ADD is_last_activity_by_client BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('
            UPDATE ticket
            SET is_last_activity_by_client = tt.is_last_client 
            FROM
            (
                SELECT MAX(ta.created_at),
                  CASE WHEN user_id IS NULL THEN TRUE ELSE FALSE END AS is_last_client,
                  ta.ticket_id
                FROM ticket_activity ta
                  INNER JOIN ticket jt
                    ON jt.id = ta.ticket_id
                GROUP BY ta.ticket_id, ta.user_id
            ) tt
            WHERE tt.ticket_id = ticket.id
        ');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket DROP is_last_activity_by_client');
    }
}
