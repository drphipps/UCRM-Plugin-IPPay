<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181213145018 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'INSERT INTO user_group_permission (group_id, module_name, permission) VALUES (1, :moduleName, :permission)',
            [
                'moduleName' => 'AppBundle\Controller\ProformaInvoiceTemplateController',
                'permission' => 'edit',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'DELETE FROM user_group_permission WHERE module_name = ?',
            [
                'AppBundle\Controller\ProformaInvoiceTemplateController',
            ]
        );
    }
}
