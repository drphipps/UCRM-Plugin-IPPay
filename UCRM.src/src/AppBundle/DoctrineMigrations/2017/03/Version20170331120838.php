<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170331120838 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            sprintf(
                'UPDATE notification_template SET body = \'%s\' WHERE body = \'%s\' OR body = \'%s\';',
                '<p>UCRM just created %CREATED_COUNT% drafts for recurring invoices. You can approve them and send as PDF to your clients.</p><p>%CREATED_LIST%</p>',
                'UCRM just created drafts for recurring invoices. You can approve them and send as PDF to your clients.',
                '<p>UCRM just created drafts for recurring invoices. You can approve them and send as PDF to your clients.</p>'
            )
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            sprintf(
                'UPDATE notification_template SET body = \'%s\' WHERE body = \'%s\';',
                '<p>UCRM just created drafts for recurring invoices. You can approve them and send as PDF to your clients.</p>',
                '<p>UCRM just created %CREATED_COUNT% drafts for recurring invoices. You can approve them and send as PDF to your clients.</p><p>%CREATED_LIST%</p>'
            )
        );
    }
}
