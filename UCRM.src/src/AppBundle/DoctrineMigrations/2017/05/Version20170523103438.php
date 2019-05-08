<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170523103438 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('
        CREATE VIEW client_logs_view AS
            SELECT
                log_id,
                message,
                created_date,
                \'client_log\' AS log_type,
                client_id
              FROM client_log
            UNION
              SELECT
                log_id,
                message,
                created_date,
                \'email_log\' AS log_type,
                client_id
              FROM email_log
            UNION
              SELECT
                log_id,
                log AS message,
                created_date,
                \'entity_log\' AS log_type,
                client_id
              FROM entity_log
            ORDER BY created_date DESC
        ');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP VIEW client_logs_view');
    }
}
