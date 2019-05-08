<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170331144714 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                62,
                'NOTIFICATION_CREATED_DRAFTS',
                '1',
            ]
        );
        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                63,
                'NOTIFICATION_CREATED_INVOICES',
                '1',
            ]
        );

        $this->addSql('UPDATE notification_template SET body = REPLACE(body, \'%CREATED_DRAFTS_COUNT%\', \'%CREATED_COUNT%\')');
        $this->addSql(
            sprintf(
                'INSERT INTO notification_template (template_id, subject, body, type) VALUES (17, \'Recurring invoices have been created\', \'%s\', 17);',
                '<p>UCRM just created %CREATED_COUNT% recurring invoices. They were sent as PDF to your clients.</p><p>%CREATED_LIST%</p>'
            )
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE option_id IN (62, 63)');
        $this->addSql('DELETE FROM notification_template WHERE template_id = 17');
        $this->addSql('UPDATE notification_template SET body = REPLACE(body, \'%CREATED_COUNT%\', \'%CREATED_DRAFTS_COUNT%\')');
    }
}
