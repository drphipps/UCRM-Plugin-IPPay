<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180604142914 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'DELETE FROM option WHERE code IN (\'BACKUP_REMOTE_DROPBOX_APP\', \'BACKUP_REMOTE_DROPBOX_SECRET\')'
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

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
}
