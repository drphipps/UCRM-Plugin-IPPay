<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170130123618 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO service_stop_reason (reason_id, name) VALUES (2, \'Service not yet active\')');
        $this->addSql('UPDATE notification_template SET subject = \'Service suspended\' WHERE template_id IN (8, 9)');
        $this->addSql('UPDATE notification_template SET subject = \'Service terminated\' WHERE template_id = 14');
        $this->addSql(
            sprintf(
                'INSERT INTO notification_template (template_id, subject, body, type) VALUES (15, \'Service prepared\', \'%s\', 15);',
                "<p>Dear %CLIENT_NAME%,<br />\nyour internet service %SERVICE_TARIFF% is not yet active.</p>"
            )
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM service_stop_reason WHERE reason_id = 2');
        $this->addSql('UPDATE notification_template SET subject = \'SUSPEND_ANONYMOUS\' WHERE template_id = 8');
        $this->addSql('UPDATE notification_template SET subject = \'SUSPEND_RECOGNIZED\' WHERE template_id = 9');
        $this->addSql('UPDATE notification_template SET subject = \'SUSPEND TERMINATED\' WHERE template_id = 14');
        $this->addSql('DELETE FROM notification_template WHERE template_id = 15');
    }
}
