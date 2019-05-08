<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170224121223 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO "user_group_special_permission" ("group_id", "module_name", "permission") VALUES (1, \'SHOW_DEVICE_PASSWORDS\', \'allow\');');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM "user_group_special_permission" WHERE "module_name" = \'SHOW_DEVICE_PASSWORDS\';');
    }
}
