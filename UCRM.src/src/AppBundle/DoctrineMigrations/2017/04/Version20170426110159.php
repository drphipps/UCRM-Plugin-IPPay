<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170426110159 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                77,
                'BACKUP_INCLUDE_INVOICE_TEMPLATES',
                1,
            ]
        );
        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                78,
                'BACKUP_INCLUDE_SSL_CERTIFICATES',
                1,
            ]
        );
        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                79,
                'BACKUP_INCLUDE_DOWNLOADS',
                1,
            ]
        );
        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                80,
                'BACKUP_INCLUDE_MEDIA',
                1,
            ]
        );
        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                81,
                'BACKUP_INCLUDE_WEBROOT',
                1,
            ]
        );
        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                82,
                'BACKUP_INCLUDE_DOCUMENTS',
                1,
            ]
        );
        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                83,
                'BACKUP_LIFETIME_COUNT',
                30,
            ]
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE option_id IN (77, 78, 79, 80, 81, 82, 83)');
    }
}
