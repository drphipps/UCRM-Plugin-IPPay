<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171023084213 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO user_group_permission (group_id, module_name, permission) VALUES (1, \'AppBundle\Controller\NotificationSettingsController\', \'edit\')');
        $this->addSql('INSERT INTO user_group_permission (group_id, module_name, permission) VALUES (1, \'AppBundle\Controller\SuspensionTemplatesController\', \'edit\')');
        $this->addSql('UPDATE user_group_permission SET module_name = \'AppBundle\Controller\EmailTemplatesController\' WHERE module_name = \'AppBundle\Controller\NotificationController\'');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM user_group_permission WHERE module_name IN (?, ?)', [
            'AppBundle\Controller\NotificationSettingsController',
            'AppBundle\Controller\SuspensionTemplatesController',
        ]);
        $this->addSql('UPDATE user_group_permission SET module_name = \'AppBundle\Controller\NotificationController\' WHERE module_name = \'AppBundle\Controller\EmailTemplatesController\'');
    }
}
