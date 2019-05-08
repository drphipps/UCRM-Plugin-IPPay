<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170529135502 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql(
            '
            CREATE VIEW job_logs_view AS
                  SELECT
                    log_id,
                    log AS message,
                    created_date,
                    \'entity_log\' AS log_type,
                    entity_id AS job_id
                  FROM entity_log
                  WHERE entity LIKE \'%Entity\\\\Job\'
            UNION
                  SELECT
                    id  AS log_id,
                    message,
                    created_date,
                    \'job_comment\' AS log_type,
                    job_id
                  FROM job_comment
            ORDER BY created_date DESC
        '
        );

        $this->addSql('INSERT INTO "user_group_special_permission" ("group_id", "module_name", "permission") VALUES (1, \'JOB_COMMENT_EDIT\', \'allow\');');
    }

    public function down(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql('DROP VIEW job_logs_view');

        $this->addSql('DELETE FROM "user_group_special_permission" WHERE "module_name" = \'JOB_COMMENT_EDIT\';');
    }
}
