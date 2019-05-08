<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160714144114 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('DELETE FROM "user_group_permission" WHERE "module_name" = \'AppBundle\Controller\HomepageController\';');
        $this->addSql('DELETE FROM "user_group_permission" WHERE "module_name" = \'AppBundle\Controller\ResetPasswordController\';');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (5, 1, \'AppBundle\Controller\HomepageController\', \'edit\')');
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (12, 1, \'AppBundle\Controller\ResetPasswordController\', \'edit\')');
    }
}
