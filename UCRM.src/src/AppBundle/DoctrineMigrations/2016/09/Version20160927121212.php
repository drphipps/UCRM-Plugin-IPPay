<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160927121212 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('UPDATE setting_category SET name = \'outage\' WHERE category_id = 5');
    }

    public function down(Schema $schema)
    {
        $this->addSql('UPDATE setting_category SET name = \'ping\' WHERE category_id = 5');
    }
}
