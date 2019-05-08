<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180213150029 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            '
                INSERT INTO general 
                    (code, value)
                SELECT 
                    \'wizard_account_done\',
                    CASE WHEN EXISTS(SELECT user_id FROM "user" WHERE role = \'ROLE_WIZARD\' LIMIT 1)
                        THEN \'0\'
                        ELSE \'1\'
                    END
            '
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'DELETE FROM general WHERE code = ?',
            [
                'wizard_account_done',
            ]
        );
    }
}
