<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170530071512 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('
            INSERT INTO general (code, value)
            SELECT \'suspend_synchronized\', \'0\'
            WHERE NOT EXISTS (
                SELECT general_id FROM general WHERE code = \'suspend_synchronized\'
            )
        ');
    }

    public function down(Schema $schema)
    {
        // Intentionally empty.
    }
}
