<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180604120120 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO user_group_special_permission ("group_id", "module_name", "permission")
            SELECT ugsp.group_id, \'FINANCIAL_OVERVIEW\', ugsp.permission
            FROM user_group_special_permission ugsp
            WHERE module_name = \'CLIENT_ACCOUNT_STANDING\'');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM "user_group_special_permission" WHERE "module_name" = \'FINANCIAL_OVERVIEW\';');
    }
}
