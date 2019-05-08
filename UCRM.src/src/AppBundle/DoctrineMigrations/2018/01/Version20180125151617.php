<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180125151617 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            sprintf(
                'UPDATE notification_template SET body = \'%s\' WHERE template_id = 21 AND body = \'%s\'',
                '<p>Dear %CLIENT_NAME%.<br />We changed status of your ticket number %TICKET_ID%.</p><p>Status: %TICKET_STATUS%<br>Subject: %TICKET_SUBJECT%<br>Link to ticket: <a href="%TICKET_URL%">%TICKET_URL%</a></p>',
                '<p>Dear %CLIENT_NAME%.<br />
We changed status of your ticket number %TICKET_ID%.</p><p>Status: %TICKET_STATUS%<br>Subject: %TICKET_SUBJECT%</p>'
            )
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            sprintf(
                'UPDATE notification_template SET body = \'%s\' WHERE template_id = 21 AND body = \'%s\'',
                '<p>Dear %CLIENT_NAME%.<br />
We changed status of your ticket number %TICKET_ID%.</p><p>Status: %TICKET_STATUS%<br>Subject: %TICKET_SUBJECT%</p>',
                '<p>Dear %CLIENT_NAME%.<br />We changed status of our ticket number %TICKET_ID%.</p><p>Status: %TICKET_STATUS%<br>Subject: %TICKET_SUBJECT%<br>Link to ticket: <a href="%TICKET_URL%">%TICKET_URL%</a></p>'
            )
        );
    }
}
