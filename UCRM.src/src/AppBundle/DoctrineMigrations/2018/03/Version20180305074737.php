<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180305074737 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE client_zone_page (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, public BOOLEAN DEFAULT \'false\' NOT NULL, content TEXT DEFAULT NULL, position SMALLINT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO user_group_permission (group_id, module_name, permission) VALUES (1, \'AppBundle\Controller\ClientZonePageController\', \'edit\')');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE client_zone_page');
        $this->addSql('DELETE FROM user_group_permission WHERE module_name=\'AppBundle\Controller\ClientZonePageController\'');
    }
}
