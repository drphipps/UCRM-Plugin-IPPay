<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170724121804 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE INDEX IDX_6FB4883ADE9C86E ON email_log (created_date)');
        $this->addSql('ALTER INDEX date_idx RENAME TO IDX_F1B00862ADE9C86E');
        $this->addSql('CREATE INDEX IDX_A89BFB61ADE9C86E ON client_log (created_date)');

        $this->addSql('DROP VIEW client_logs_view');

        $this->addSql('
        CREATE VIEW client_logs_view AS
            SELECT
                log_id * 3 AS id,
                log_id,
                message,
                created_date,
                \'client_log\' AS log_type,
                client_id
              FROM client_log
            UNION
              SELECT
                log_id * 3 + 1 AS id,
                log_id,
                message,
                created_date,
                \'email_log\' AS log_type,
                client_id
              FROM email_log
            UNION
              SELECT
                log_id * 3 + 2 AS id,
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

        $this->addSql('DROP INDEX IDX_6FB4883ADE9C86E');
        $this->addSql('DROP INDEX IDX_A89BFB61ADE9C86E');
        $this->addSql('ALTER INDEX idx_f1b00862ade9c86e RENAME TO date_idx');

        $this->addSql('DROP VIEW client_logs_view');

        $this->addSql('
        CREATE VIEW client_logs_view AS
            SELECT row_number() OVER (ORDER BY created_date) AS id, t.*
            FROM (
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
            ) t
        ');
    }
}
