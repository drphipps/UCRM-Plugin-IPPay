<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171030102448 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE user_group_permission SET module_name = \'AppBundle\Controller\AppearanceController\' WHERE module_name = \'AppBundle\Controller\CustomCssController\'');
        $this->addSql('INSERT INTO general (code, value) VALUES (\'appearance_favicon\', null)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE user_group_permission SET module_name = \'AppBundle\Controller\CustomCssController\' WHERE module_name = \'AppBundle\Controller\AppearanceController\'');
        $this->addSql('DELETE FROM general WHERE code = \'appearance_favicon\'');
    }
}
