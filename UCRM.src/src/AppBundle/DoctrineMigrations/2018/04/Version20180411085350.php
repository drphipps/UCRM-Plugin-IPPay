<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180411085350 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('
            UPDATE
              service s
            SET
              tax_id1 = NULL,
              tax_id2 = NULL,
              tax_id3 = NULL
            FROM
              tariff t
            WHERE
              s.tariff_id = t.tariff_id
              AND t.taxable = true
              AND t.tax_id IS NOT NULL
        ');
    }

    public function down(Schema $schema): void
    {
    }
}
