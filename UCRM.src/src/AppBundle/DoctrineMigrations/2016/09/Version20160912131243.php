<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160912131243 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('UPDATE option SET value = NULL WHERE code = \'SITE_NAME\' AND value = \'A site name\'');
        $this->addSql('UPDATE option SET description = \'Site name is displayed on login screen (in case default organization has no logo), in title text of every page and in client zone header.\' WHERE code = \'SITE_NAME\'');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE option SET value = \'A site name\' WHERE code = \'SITE_NAME\' AND value IS NULL');
        $this->addSql('UPDATE option SET description = \'Organization site name used for this app.\' WHERE code = \'SITE_NAME\'');
    }
}
