<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160816124853 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM "user_group_special_permission";');

        $this->addSql('INSERT INTO "user_group_special_permission" ("special_permission_id", "group_id", "module_name", "permission") VALUES (1, 1, \'CLIENT_IMPERSONATION\', \'allow\');');
        $this->addSql('INSERT INTO "user_group_special_permission" ("special_permission_id", "group_id", "module_name", "permission") VALUES (2, 1, \'CLIENT_ACCOUNT_STANDING\', \'allow\');');
        $this->addSql('ALTER SEQUENCE "user_group_special_permission_special_permission_id_seq" RESTART WITH 3');

        // billing controller has been joined with invoice controller for permissions
        $this->addSql('DELETE FROM "user_group_permission" WHERE "module_name" = \'AppBundle\Controller\BillingController\';');
    }

    public function down(Schema $schema)
    {
        $this->addSql('DELETE FROM "user_group_special_permission" WHERE "module_name" = \'CLIENT_IMPERSONATION\';');
        $this->addSql('DELETE FROM "user_group_special_permission" WHERE "module_name" = \'CLIENT_ACCOUNT_STANDING\';');
        $this->addSql('INSERT INTO "user_group_permission" ("permission_id", "group_id", "module_name", "permission") VALUES (\'1\', \'1\', \'AppBundle\Controller\BillingController\', \'edit\');');
    }
}
