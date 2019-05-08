<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180123131231 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO user_group_permission (group_id, module_name, permission) VALUES (1, \'AppBundle\Controller\PluginController\', \'edit\')');
        $this->addSql('CREATE TABLE plugin (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, display_name VARCHAR(255) NOT NULL, description TEXT NOT NULL, url VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL, version VARCHAR(100) NOT NULL, min_ucrm_version VARCHAR(100) NOT NULL, max_ucrm_version VARCHAR(100) DEFAULT NULL, enabled BOOLEAN DEFAULT \'false\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E96E27945E237E06 ON plugin (name)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM user_group_permission WHERE module_name=\'AppBundle\Controller\PluginController\'');
        $this->addSql('DROP TABLE plugin');
    }
}
