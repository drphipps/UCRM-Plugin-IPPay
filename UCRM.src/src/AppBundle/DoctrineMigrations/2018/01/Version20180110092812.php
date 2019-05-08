<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180110092812 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('
            UPDATE ticket_comment tc
            SET inbox_id = (
              SELECT id
              FROM ticket_imap_inbox
              WHERE is_default = TRUE
              LIMIT 1
            )
            FROM ticket t
              JOIN ticket_activity ta ON t.id = ta.ticket_id
              JOIN ticket_comment tc2 ON ta.id = tc2.id
            WHERE tc.id = tc2.id
              AND t.email_from_address IS NOT NULL
              AND tc.inbox_id IS NULL;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
    }
}
