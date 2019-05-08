<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160927164040 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('UPDATE option SET help = \'outage-monitoring\' WHERE code = \'PING_OUTAGE_THRESHOLD\'');
    }

    public function down(Schema $schema)
    {
        $this->addSql('UPDATE option SET help = NULL WHERE code = \'PING_OUTAGE_THRESHOLD\'');
    }
}
