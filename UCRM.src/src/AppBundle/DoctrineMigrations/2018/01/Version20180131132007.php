<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180131132007 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE notification_template SET subject = subject || \' #%TICKET_ID%\' WHERE template_id = 19 AND subject NOT LIKE \'%%TICKET_ID%%\'');
        $this->addSql('UPDATE notification_template SET subject = subject || \' #%TICKET_ID%\' WHERE template_id = 21 AND subject NOT LIKE  \'%%TICKET_ID%%\'');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
    }
}
