<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170214202020 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
        $this->addSql(
            'INSERT INTO locale (locale_id, code, name) VALUES (?, ?, ?)',
            [
                2,
                'es_ES',
                'Spanish',
            ]
        );
        $this->addSql(
            'INSERT INTO locale (locale_id, code, name) VALUES (?, ?, ?)',
            [
                3,
                'ca_ES',
                'Catalan',
            ]
        );
        $this->addSql(
            'INSERT INTO locale (locale_id, code, name) VALUES (?, ?, ?)',
            [
                4,
                'tr_TR',
                'Turkish',
            ]
        );
        $this->addSql(
            'INSERT INTO locale (locale_id, code, name) VALUES (?, ?, ?)',
            [
                5,
                'nl_NL',
                'Dutch',
            ]
        );
        $this->addSql(
            'INSERT INTO locale (locale_id, code, name) VALUES (?, ?, ?)',
            [
                6,
                'lv_LV',
                'Latvian',
            ]
        );
        $this->addSql(
            'INSERT INTO locale (locale_id, code, name) VALUES (?, ?, ?)',
            [
                7,
                'sv_SE',
                'Swedish',
            ]
        );
        $this->addSql(
            'INSERT INTO locale (locale_id, code, name) VALUES (?, ?, ?)',
            [
                8,
                'pt_PT',
                'Portuguese',
            ]
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM locale WHERE locale_id IN (2, 3, 4, 5, 6, 7)');
    }
}
