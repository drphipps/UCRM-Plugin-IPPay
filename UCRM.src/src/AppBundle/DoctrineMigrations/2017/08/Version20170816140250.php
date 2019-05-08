<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170816140250 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO option (code) VALUES (\'TICKETING_IMAP_PASSWORD\')');
        $this->addSql('INSERT INTO option (code) VALUES (\'TICKETING_IMAP_SERVER_NAME\')');
        $this->addSql('INSERT INTO option (code, value) VALUES (\'TICKETING_IMAP_SERVER_PORT\', 993)');
        $this->addSql('INSERT INTO option (code) VALUES (\'TICKETING_IMAP_USERNAME\')');

        $this->addSql('INSERT INTO general (code, value) VALUES (\'ticketing_imap_last_email_uid\', null)');
        $this->addSql('INSERT INTO general (code, value) VALUES (\'ticketing_imap_setting_timestamp\', null)');

        $this->addSql('ALTER TABLE ticket_comment ADD email_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_comment ADD imap_uid INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_comment ADD email_from_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_comment ADD email_from_name VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE ticket ADD email_from_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket ADD email_from_name VARCHAR(255) DEFAULT NULL');

        $this->addSql(
            sprintf(
                'INSERT INTO notification_template (template_id, subject, body, type) VALUES (22, \'Ticket has been commented\', \'%s\', 22);',
                '<p>Message: %TICKET_MESSAGE%</p><p>Count of attachments: %TICKET_COMMENT_ATTACHMENTS_COUNT%</p>'
            )
        );

        $this->addSql('CREATE TABLE ticket_comment_mail_attachment (id SERIAL NOT NULL, ticket_comment_id INT DEFAULT NULL, filename TEXT NOT NULL, mime_type TEXT NOT NULL, size INT NOT NULL, part_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8ABB24DD6EFAEF47 ON ticket_comment_mail_attachment (ticket_comment_id)');
        $this->addSql('ALTER TABLE ticket_comment_mail_attachment ADD CONSTRAINT FK_8ABB24DD6EFAEF47 FOREIGN KEY (ticket_comment_id) REFERENCES ticket_comment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE code = \'TICKETING_IMAP_USERNAME\'');
        $this->addSql('DELETE FROM option WHERE code = \'TICKETING_IMAP_PASSWORD\'');
        $this->addSql('DELETE FROM option WHERE code = \'TICKETING_IMAP_SERVER_NAME\'');
        $this->addSql('DELETE FROM option WHERE code = \'TICKETING_IMAP_SERVER_PORT\'');

        $this->addSql('DELETE FROM general WHERE code = \'ticketing_imap_last_email_uid\'');
        $this->addSql('DELETE FROM general WHERE code = \'ticketing_imap_setting_timestamp\'');

        $this->addSql('ALTER TABLE ticket_comment DROP email_id');
        $this->addSql('ALTER TABLE ticket_comment DROP imap_uid');
        $this->addSql('ALTER TABLE ticket_comment DROP email_from_address');
        $this->addSql('ALTER TABLE ticket_comment DROP email_from_name');

        $this->addSql('ALTER TABLE ticket DROP email_from_address');
        $this->addSql('ALTER TABLE ticket DROP email_from_name');

        $this->addSql('DELETE FROM notification_template WHERE template_id = 22');

        $this->addSql('DROP TABLE ticket_comment_mail_attachment');
    }
}
