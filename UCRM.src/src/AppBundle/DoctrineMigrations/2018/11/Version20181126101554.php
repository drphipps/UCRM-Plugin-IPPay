<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181126101554 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
        // Set
        $this->addSql(
            'INSERT INTO option (code, value) VALUES (
                ?,
                (CASE
                    WHEN EXISTS(SELECT organization_id FROM organization WHERE country_id NOT IN(249, 54) LIMIT 1)
                    THEN \'EU\'
                    ELSE \'US\'
                END)
            )',
            [
                'BALANCE_STYLE',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'DELETE FROM option WHERE code = ?',
            [
                'BALANCE_STYLE',
            ]
        );
    }
}
