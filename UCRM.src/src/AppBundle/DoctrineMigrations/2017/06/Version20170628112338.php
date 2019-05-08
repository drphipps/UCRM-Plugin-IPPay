<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170628112338 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            sprintf(
                'INSERT INTO notification_template (template_id, subject, body, type) VALUES (19, \'Ticket has been created\', \'%s\', 19);',
                "<p>Dear %CLIENT_NAME%.<br />\nWe created ticket number %TICKET_ID% on your request.</p><p>Status: %TICKET_STATUS%<br>Subject: %TICKET_SUBJECT%<br>Link to ticket: <a href=\"%TICKET_URL%\">%TICKET_URL%</a></p>"
            )
        );

        $this->addSql(
            sprintf(
                'INSERT INTO notification_template (template_id, subject, body, type) VALUES (20, \'Ticket has been commented\', \'%s\', 20);',
                "<p>Dear %CLIENT_NAME%.<br />\nWe commented ticket number %TICKET_ID%.</p><p>Status: %TICKET_STATUS%<br>Subject: %TICKET_SUBJECT%<br>Link to ticket: <a href=\"%TICKET_URL%\">%TICKET_URL%</a></p>"
            )
        );

        $this->addSql(
            sprintf(
                'INSERT INTO notification_template (template_id, subject, body, type) VALUES (21, \'Ticket changed status\', \'%s\', 21);',
                "<p>Dear %CLIENT_NAME%.<br />\nWe changed status of your ticket number %TICKET_ID%.</p><p>Status: %TICKET_STATUS%<br>Subject: %TICKET_SUBJECT%</p>"
            )
        );

        $this->addSql("INSERT INTO option(code, value) VALUES ('NOTIFICATION_TICKET_CLIENT_CREATED_BY_EMAIL', 1)");

        $this->addSql("INSERT INTO option(code, value) VALUES ('NOTIFICATION_TICKET_CLIENT_CREATED_IN_HEADER', 0)");

        $this->addSql("INSERT INTO option(code, value) VALUES ('NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_IN_HEADER', 0)");

        $this->addSql("INSERT INTO option(code, value) VALUES ('NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_BY_EMAIL', 1)");

        $this->addSql("INSERT INTO option(code, value) VALUES ('NOTIFICATION_TICKET_COMMENT_USER_CREATED_BY_EMAIL', 1)");

        $this->addSql("INSERT INTO option(code, value) VALUES ('NOTIFICATION_TICKET_USER_CHANGED_STATUS', 0)");

        $this->addSql("INSERT INTO option(code, value) VALUES ('NOTIFICATION_TICKET_USER_CREATED_BY_EMAIL', 1)");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM notification_template WHERE template_id IN (19, 20, 21)');

        $this->addSql("DELETE FROM option WHERE code = 'NOTIFICATION_TICKET_CLIENT_CREATED_BY_EMAIL'");

        $this->addSql("DELETE FROM option WHERE code = 'NOTIFICATION_TICKET_CLIENT_CREATED_IN_HEADER'");

        $this->addSql("DELETE FROM option WHERE code = 'NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_BY_EMAIL'");

        $this->addSql("DELETE FROM option WHERE code = 'NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_IN_HEADER'");

        $this->addSql("DELETE FROM option WHERE code = 'NOTIFICATION_TICKET_COMMENT_USER_CREATED_BY_EMAIL'");

        $this->addSql("DELETE FROM option WHERE code = 'NOTIFICATION_TICKET_USER_CHANGED_STATUS'");

        $this->addSql("DELETE FROM option WHERE code = 'NOTIFICATION_TICKET_USER_CREATED_BY_EMAIL'");
    }
}
