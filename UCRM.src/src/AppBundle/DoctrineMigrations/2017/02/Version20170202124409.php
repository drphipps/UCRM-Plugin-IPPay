<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170202124409 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                55,
                'FORMAT_DATE',
                '1',
            ]
        );

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                56,
                'FORMAT_DATE_SHORT',
                '9',
            ]
        );

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                57,
                'FORMAT_TIME',
                '1',
            ]
        );

        $this->addSql('ALTER TABLE locale DROP moment_js_date_format');
        $this->addSql('ALTER TABLE locale DROP moment_js_time_format');
        $this->addSql('ALTER TABLE locale DROP moment_js_date_time_format');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE option_id IN (55, 56, 57)');

        $this->addSql('ALTER TABLE locale ADD moment_js_date_format VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE locale ADD moment_js_time_format VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE locale ADD moment_js_date_time_format VARCHAR(40) NOT NULL');
    }
}
