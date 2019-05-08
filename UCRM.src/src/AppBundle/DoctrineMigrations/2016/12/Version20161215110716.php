<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20161215110716 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE locale (locale_id SERIAL NOT NULL, code VARCHAR(10) NOT NULL, name VARCHAR(50) NOT NULL, moment_js_date_format VARCHAR(20) NOT NULL, moment_js_time_format VARCHAR(20) NOT NULL, moment_js_date_time_format VARCHAR(40) NOT NULL, PRIMARY KEY(locale_id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4180C69877153098 ON locale (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3701B2975E237E06 ON timezone (name)');

        $this->addSql(
            'INSERT INTO locale (code, name, moment_js_date_format, moment_js_time_format, moment_js_date_time_format) VALUES (?, ?, ?, ?, ?)',
            [
                'en_US',
                'English (US)',
                'M/DD/YY',
                'h:mm a',
                'M/DD/YY h:mm a',
            ]
        );

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                49,
                'APP_LOCALE',
                'en_US',
            ]
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX UNIQ_3701B2975E237E06');
        $this->addSql('DROP TABLE locale');
        $this->addSql('DELETE FROM option WHERE option_id = ?', [49]);
    }
}
