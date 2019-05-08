<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171103114816 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE notification_template SET subject = \'%TICKET_SUBJECT% (Ticket #%TICKET_ID%)\' WHERE subject = \'Ticket has been commented\' AND template_id = 22');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
    }
}
