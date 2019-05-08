<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170216141255 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            sprintf(
                'INSERT INTO notification_template (template_id, subject, body, type) VALUES (16, \'Invoice near due date\', \'%s\', 16);',
                "<p>Dear %CLIENT_NAME%,<br />\nThis is a friendly reminder that your invoice %INVOICE_NUMBER% is due on %INVOICE_DUE_DATE%.</p>"
            )
        );
        $this->addSql('ALTER TABLE invoice ADD near_due_notification_sent BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                58,
                'NOTIFICATION_INVOICE_NEAR_DUE',
                0,
            ]
        );
        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                59,
                'NOTIFICATION_INVOICE_NEAR_DUE_DAYS',
                7,
            ]
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM notification_template WHERE template_id = 16');
        $this->addSql('ALTER TABLE invoice DROP near_due_notification_sent');
        $this->addSql('DELETE FROM option WHERE option_id IN (58, 59)');
    }
}
