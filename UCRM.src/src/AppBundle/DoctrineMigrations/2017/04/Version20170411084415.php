<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170411084415 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE option ALTER code TYPE VARCHAR(128)');

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                64,
                'NOTIFICATION_INVOICE_NEW',
                '1',
            ]
        );

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                65,
                'NOTIFICATION_INVOICE_OVERDUE',
                '1',
            ]
        );

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                66,
                'NOTIFICATION_SERVICE_SUSPENDED',
                '1',
            ]
        );

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                67,
                'NOTIFICATION_SERVICE_SUSPENSION_POSTPONED',
                '1',
            ]
        );

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                68,
                'NOTIFICATION_SUBSCRIPTION_CANCELLED',
                '1',
            ]
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE option_id IN (64, 65, 66, 67, 68)');
        $this->addSql('ALTER TABLE option ALTER code TYPE VARCHAR(40)');
    }
}
