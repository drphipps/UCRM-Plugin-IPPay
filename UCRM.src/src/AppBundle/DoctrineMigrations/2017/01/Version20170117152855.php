<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170117152855 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                51,
                'LOG_LIFETIME_DEVICE',
                '90',
            ]
        );

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                52,
                'LOG_LIFETIME_EMAIL',
                '720',
            ]
        );

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                53,
                'LOG_LIFETIME_ENTITY',
                '90',
            ]
        );

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                54,
                'LOG_LIFETIME_SERVICE_DEVICE',
                '90',
            ]
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql('DELETE FROM option WHERE option_id IN (51,52,53,54)');
    }
}
