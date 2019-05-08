<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171013085747 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'INSERT INTO notification_template (template_id, subject, body, type)
             SELECT
              24,
              \'Ticket has been commented\',
              CASE  body
               WHEN \'<p>Dear %CLIENT_NAME%.<br />
We commented ticket number %TICKET_ID%.</p><p>Status: %TICKET_STATUS%<br>Subject: %TICKET_SUBJECT%<br>Link to ticket: <a href="%TICKET_URL%">%TICKET_URL%</a></p>\'
               THEN \'<p>Dear %CLIENT_NAME%.<br />We commented ticket number %TICKET_ID%.</p><p>Message: %TICKET_MESSAGE%<p>Status: %TICKET_STATUS%<br>Subject: %TICKET_SUBJECT%<br>Link to ticket: <a href="%TICKET_URL%">%TICKET_URL%</a></p>\'
               ELSE CONCAT(body, \'<br>Message: %TICKET_MESSAGE%\')
              END
              ,
              24
             FROM notification_template
             WHERE template_id = 20;'
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM notification_template WHERE template_id = 23');
    }
}
