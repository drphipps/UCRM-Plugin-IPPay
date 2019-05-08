<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180111153141 extends AbstractMigration
{
    /**
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql(
            'INSERT INTO option (code, value) VALUES (?, ?)',
            [
                'BACKUP_REMOTE_DROPBOX',
                '',
            ]
        );
        $this->addSql(
            'INSERT INTO option (code, value) VALUES (?, ?)',
            [
                'BACKUP_REMOTE_DROPBOX_TOKEN',
                '',
            ]
        );

        $this->addSql(
            'INSERT INTO option (code, value) VALUES (?, ?)',
            [
                'BACKUP_REMOTE_DROPBOX_APP',
                '',
            ]
        );
        $this->addSql(
            'INSERT INTO option (code, value) VALUES (?, ?)',
            [
                'BACKUP_REMOTE_DROPBOX_SECRET',
                '',
            ]
        );
    }

    /**
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql(
            'DELETE FROM option WHERE code IN (\'BACKUP_REMOTE_DROPBOX_TOKEN\', \'BACKUP_REMOTE_DROPBOX\', \'BACKUP_REMOTE_DROPBOX_APP\', \'BACKUP_REMOTE_DROPBOX_SECRET\')'
        );
    }
}
