<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170411140003 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql(
            '
              INSERT INTO general (code, value)
              SELECT \'netflow_last_aggregation_timestamp\', \'60\'
              WHERE NOT EXISTS (
                SELECT code FROM general WHERE code = \'netflow_last_aggregation_timestamp\'
              )
            '
        );
        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, ?)',
            [
                73,
                'NETFLOW_AGGREGATION_FREQUENCY',
                '60',
            ]
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql('DELETE FROM general WHERE code = \'netflow_last_aggregation_timestamp\'');
        $this->addSql('DELETE FROM option WHERE option_id = 73');
    }
}
