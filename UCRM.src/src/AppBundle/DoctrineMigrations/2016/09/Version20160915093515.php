<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160915093515 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO setting_category (category_id, name) VALUES (6, \'qos\')');
        $this->addSql('INSERT INTO option (option_id, category_id, position, code, name, description, type, value, choice_type_options) VALUES (42, 6, 0, \'QOS_ENABLED\', \'QoS enabled\', NULL, \'toggle\', 0, null)');
        $this->addSql('INSERT INTO option (option_id, category_id, position, code, name, description, type, value, choice_type_options) VALUES (43, 6, 1, \'QOS_SYNC_TYPE\', \'QoS sync type\', NULL, \'choice\', \'address_lists\', \'{"choices":{"address_lists":"Address lists"}}\')');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE option_id IN (42, 43)');
        $this->addSql('DELETE FROM setting_category WHERE category_id = 6');
    }
}
