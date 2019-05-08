<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160824114844 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('UPDATE "option" SET "position" = "position" + 1 WHERE "category_id" = 2 AND "position" > 4;');

        $this->addSql(
            'INSERT INTO "option" ("option_id", "category_id", "position", "code", "name", "description", "type", "value", "value_txt", "choice_type_options", "validation_rules", "help")' .
            'VALUES (\'40\', \'2\', \'5\', \'SEND_INVOICE_WITH_ZERO_BALANCE\', \'Send invoices with zero balance\', \'This option takes effect only for automatically sent invoices. If turned off, only invoices with balance higher than 0 will be sent.\', \'toggle\', \'1\', \'\', NULL, NULL, \'\');'
        );
    }

    public function down(Schema $schema)
    {
        $this->addSql('DELETE FROM "option" WHERE "code" = \'SEND_INVOICE_WITH_ZERO_BALANCE\';');
        $this->addSql('UPDATE "option" SET "position" = "position" - 1 WHERE "category_id" = 2 AND "position" > 4;');
    }
}
