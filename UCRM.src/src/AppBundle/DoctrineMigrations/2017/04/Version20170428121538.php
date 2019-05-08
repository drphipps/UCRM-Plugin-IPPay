<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170428121538 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                84,
                'MAILER_ANTIFLOOD_LIMIT_COUNT',
                null,
            ]
        );
        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                85,
                'MAILER_ANTIFLOOD_SLEEP_TIME',
                null,
            ]
        );
        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                86,
                'MAILER_THROTTLER_LIMIT_COUNT',
                null,
            ]
        );
        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                87,
                'MAILER_THROTTLER_LIMIT_TIME',
                null,
            ]
        );

        $this->addSql('INSERT INTO general (code, value) VALUES (\'mailer_antiflood_counter\', \'0\')');
        $this->addSql('INSERT INTO general (code, value) VALUES (\'mailer_antiflood_timestamp\', null)');
        $this->addSql('INSERT INTO general (code, value) VALUES (\'mailer_throttler_counter\', \'0\')');
        $this->addSql('INSERT INTO general (code, value) VALUES (\'mailer_throttler_timestamp\', null)');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE option_id IN (84, 85, 86, 87)');
        $this->addSql('
          DELETE FROM general WHERE code IN (
            \'mailer_antiflood_counter\',
            \'mailer_antiflood_timestamp\',
            \'mailer_throttler_counter\',
            \'mailer_throttler_timestamp\'
          )
        ');
    }
}
