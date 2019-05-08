<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180416134111 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket_comment ADD email_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN ticket_comment.email_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE ticket_imap_inbox DROP last_email_uid');
        $this->addSql('ALTER TABLE ticket_imap_inbox ADD import_start_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN ticket_imap_inbox.import_start_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE ticket_imap_inbox DROP created_at');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket_comment DROP email_date');
        $this->addSql('ALTER TABLE ticket_imap_inbox ADD last_email_uid INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_imap_inbox DROP import_start_date');
        $this->addSql('ALTER TABLE ticket_imap_inbox ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN ticket_imap_inbox.created_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('UPDATE ticket_imap_inbox SET created_at = import_start_date');
        $this->addSql('ALTER TABLE ticket_imap_inbox ALTER COLUMN created_at SET NOT NULL');
    }
}
