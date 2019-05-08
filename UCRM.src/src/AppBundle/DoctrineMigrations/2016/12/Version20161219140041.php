<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20161219140041 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM user_group_permission WHERE permission_id = 25'); // AppBundle\Controller\UcrmStatsController
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (40, 1, \'AppBundle\Controller\SettingBillingController\', \'edit\')');
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (41, 1, \'AppBundle\Controller\SettingSuspendController\', \'edit\')');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (25, 1, \'AppBundle\Controller\UcrmStatsController\', \'edit\')');
        $this->addSql('DELETE FROM user_group_permission WHERE permission_id = 40');
        $this->addSql('DELETE FROM user_group_permission WHERE permission_id = 41');
    }
}
