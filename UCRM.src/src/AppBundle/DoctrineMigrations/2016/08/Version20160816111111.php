<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160816111111 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE option SET name = \'Billing cycle\',  description = \'Determines whether pro-rated periods should always use 30 days in month or real days count of current month to calculate quantity.\', choice_type_options = \'{"choices":{"0":"Real days count","1":"30 days in month"}}\' WHERE option_id = 34');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE option SET name = \'Billing cycle type\',  description = \'Determines if pro-rated periods should always use 30 days in month, or real day count to calculate quantity.\', choice_type_options = \'{"choices":{"0":"Real day count","1":"30 days in month"}}\' WHERE option_id = 34');
    }
}
