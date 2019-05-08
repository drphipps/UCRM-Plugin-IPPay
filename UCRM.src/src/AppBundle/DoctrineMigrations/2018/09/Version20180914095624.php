<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180914095624 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('
            CREATE VIEW service_accounting_view AS
            SELECT
                accounting_id AS id,
                1 AS type,
                service_id,
                download,
                upload,
                date
              FROM service_accounting
            UNION ALL
              SELECT
                id,
                2 AS type,
                service_id,
                download,
                upload,
                date
              FROM service_accounting_correction
        ');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP VIEW service_accounting_view');
    }
}
