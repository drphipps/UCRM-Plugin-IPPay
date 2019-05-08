<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171022123012 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO option (code, value) VALUES (\'TICKETING_IMAP_AUTOMATIC_REPLY_ENABLED\', 0)');

        $this->addSql(
            sprintf(
                'INSERT INTO notification_template (template_id, subject, body, type) VALUES (26, \'Thank you for your request (Ticket #%%TICKET_ID%%)\', \'%s\', 26)',
                '<p>Dear customer,</p><p>your request has been received and a Ticket with number&nbsp;%TICKET_ID%&nbsp;has been created. We will deal with your request as soon as possible.</p><p>Ticket subject:&nbsp;%TICKET_SUBJECT%<br />Ticket message: %TICKET_MESSAGE%</p>'
            )
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE code = \'TICKETING_IMAP_AUTOMATIC_REPLY_ENABLED\'');

        $this->addSql('DELETE FROM notification_template WHERE template_id = 26');
    }
}
