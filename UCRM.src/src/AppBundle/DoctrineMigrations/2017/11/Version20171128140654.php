<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171128140654 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE ticket_imap_inbox (id SERIAL NOT NULL, ticket_group_id INT DEFAULT NULL, server_name VARCHAR(255) DEFAULT NULL, server_port INT DEFAULT 993 NOT NULL, email_address VARCHAR(255) DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, is_default BOOLEAN DEFAULT \'false\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, last_email_uid INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_40057D637B3D25C6 ON ticket_imap_inbox (ticket_group_id)');
        $this->addSql('COMMENT ON COLUMN ticket_imap_inbox.created_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE ticket_imap_inbox ADD CONSTRAINT FK_40057D637B3D25C6 FOREIGN KEY (ticket_group_id) REFERENCES ticket_group (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('    
          INSERT INTO ticket_imap_inbox (server_name, server_port, email_address, username, password, is_default, created_at, last_email_uid)
            VALUES (
              (SELECT value FROM option WHERE code = \'TICKETING_IMAP_SERVER_NAME\'),
              (SELECT COALESCE(value::INTEGER, 993) FROM option WHERE code = \'TICKETING_IMAP_SERVER_PORT\'),
              (SELECT value FROM option WHERE code = \'TICKETING_IMAP_EMAIL_ADDRESS\'),
              (SELECT value FROM option WHERE code = \'TICKETING_IMAP_USERNAME\'),
              (SELECT value FROM option WHERE code = \'TICKETING_IMAP_PASSWORD\'),
              \'true\',
              (SELECT to_timestamp(value::INTEGER) FROM general WHERE code = \'ticketing_imap_setting_timestamp\'),
              (SELECT value::INTEGER FROM general WHERE code = \'ticketing_imap_last_email_uid\')
            )
        ');
        $this->addSql('DELETE FROM option WHERE code = \'TICKETING_IMAP_SERVER_NAME\'');
        $this->addSql('DELETE FROM option WHERE code = \'TICKETING_IMAP_SERVER_PORT\'');
        $this->addSql('DELETE FROM option WHERE code = \'TICKETING_IMAP_EMAIL_ADDRESS\'');
        $this->addSql('DELETE FROM option WHERE code = \'TICKETING_IMAP_USERNAME\'');
        $this->addSql('DELETE FROM option WHERE code = \'TICKETING_IMAP_PASSWORD\'');
        $this->addSql('DELETE FROM general WHERE code = \'ticketing_imap_setting_timestamp\'');
        $this->addSql('DELETE FROM general WHERE code = \'ticketing_imap_last_email_uid\'');
        $this->addSql('DELETE FROM ticket_imap_inbox WHERE server_name IS NULL OR email_address IS NULL');
        $this->addSql('ALTER TABLE ticket_imap_inbox ALTER email_address SET NOT NULL');
        $this->addSql('ALTER TABLE ticket_imap_inbox ALTER server_name SET NOT NULL');
        $this->addSql('ALTER TABLE ticket_imap_inbox ALTER created_at SET NOT NULL');
        $this->addSql('ALTER TABLE ticket_comment ADD inbox_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_comment ADD CONSTRAINT FK_98B80B3E18DA89DD FOREIGN KEY (inbox_id) REFERENCES ticket_imap_inbox (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_98B80B3E18DA89DD ON ticket_comment (inbox_id)');
        $this->addSql('
            UPDATE ticket_comment tc
            SET inbox_id = (
              SELECT id
              FROM ticket_imap_inbox
              WHERE is_default = TRUE
            )
            FROM ticket t
              JOIN ticket_activity ta ON t.id = ta.ticket_id
              JOIN ticket_comment tc2 ON ta.id = tc2.id
            WHERE tc.id = tc2.id
              AND t.email_from_address IS NOT NULL;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('
            INSERT INTO option (code, value)  SELECT \'TICKETING_IMAP_SERVER_NAME\',
              CASE WHEN EXISTS(SELECT server_name FROM ticket_imap_inbox WHERE is_default = TRUE)
                THEN (SELECT server_port FROM ticket_imap_inbox WHERE is_default = TRUE)
                ELSE NULL
              END');
        $this->addSql('
            INSERT INTO option (code, value)  SELECT \'TICKETING_IMAP_SERVER_PORT\',
              CASE WHEN EXISTS(SELECT server_port FROM ticket_imap_inbox WHERE is_default = TRUE)
                THEN (SELECT server_port FROM ticket_imap_inbox WHERE is_default = TRUE)
                ELSE NULL
              END');
        $this->addSql('
            INSERT INTO option (code, value)  SELECT \'TICKETING_IMAP_EMAIL_ADDRESS\',
              CASE WHEN EXISTS(SELECT email_address FROM ticket_imap_inbox WHERE is_default = TRUE)
                THEN (SELECT email_address FROM ticket_imap_inbox WHERE is_default = TRUE)
                ELSE NULL
              END');
        $this->addSql('
            INSERT INTO option (code, value)  SELECT \'TICKETING_IMAP_USERNAME\',
              CASE WHEN EXISTS(SELECT username FROM ticket_imap_inbox WHERE is_default = TRUE)
                THEN (SELECT username FROM ticket_imap_inbox WHERE is_default = TRUE)
                ELSE NULL
              END');
        $this->addSql('
            INSERT INTO option (code, value)  SELECT \'TICKETING_IMAP_PASSWORD\',
              CASE WHEN EXISTS(SELECT password FROM ticket_imap_inbox WHERE is_default = TRUE)
                THEN (SELECT password FROM ticket_imap_inbox WHERE is_default = TRUE)
                ELSE NULL
              END');
        $this->addSql('
            INSERT INTO general (code, value)  SELECT \'ticketing_imap_setting_timestamp\',
              CASE WHEN EXISTS(SELECT created_at FROM ticket_imap_inbox WHERE is_default = TRUE)
                THEN (SELECT extract(EPOCH FROM created_at) FROM ticket_imap_inbox WHERE is_default = TRUE)
                ELSE NULL
              END');
        $this->addSql('
          INSERT INTO general (code, value)  SELECT \'ticketing_imap_last_email_uid\',
              CASE WHEN EXISTS(SELECT last_email_uid FROM ticket_imap_inbox WHERE is_default = TRUE)
                THEN (SELECT last_email_uid FROM ticket_imap_inbox WHERE is_default = TRUE)
                ELSE NULL
              END
          ');
        $this->addSql('DROP TABLE ticket_imap_inbox');
        $this->addSql('ALTER TABLE ticket_comment DROP CONSTRAINT FK_98B80B3E18DA89DD');
        $this->addSql('DROP INDEX IDX_98B80B3E18DA89DD');
        $this->addSql('ALTER TABLE ticket_comment DROP inbox_id');
    }
}
