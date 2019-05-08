<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160923134727 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('INSERT INTO setting_category (category_id, name) VALUES (7, \'sync\')');
        $this->addSql('INSERT INTO option (option_id, category_id, position, code, name, description, type, value, choice_type_options) VALUES (46, 7, 0, \'SYNC_ENABLED\', \'Device sync\', NULL, \'toggle\', 1, null)');
        $this->addSql('INSERT INTO option (option_id, category_id, position, code, name, description, type, value, choice_type_options) VALUES (47, 7, 1, \'SYNC_FREQUENCY\', \'Sync frequency\', \'Warning: Frequent synchronization can cause significant load on target devices.\', \'choice\', \'12\', \'{"choices":{"1":"1 hour","6":"6 hours","12":"12 hours","24":"24 hours"}}\')');
    }

    public function down(Schema $schema)
    {
        $this->addSql('DELETE FROM option WHERE option_id IN (46, 47)');
        $this->addSql('DELETE FROM setting_category WHERE category_id = 7');
    }
}
