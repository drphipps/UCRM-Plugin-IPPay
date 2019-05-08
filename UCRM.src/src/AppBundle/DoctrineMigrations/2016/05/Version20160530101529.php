<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160530101529 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO option (option_id, position, code, name, description, type, value, category_id, value_txt, choice_type_options) VALUES (28, 5, \'LATE_FEE_PRICE_TYPE\', \'Late fee price type\', \'If percentage type is selected, late fees will be calculated as percentage from total invoice price (even for partially paid invoices). If you need to edit the price, delete the late fee and create a custom invoice item.\', \'choice\', 1, \'4\', NULL, \'{"choices":{"1":"currency","2":"percentage"}}\')');
        $this->addSql('UPDATE option SET position = 6 WHERE option_id = 15');
        $this->addSql('UPDATE option SET type = \'float\' WHERE option_id = 17');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE option_id = 28');
        $this->addSql('UPDATE option SET position = 5 WHERE option_id = 15');
        $this->addSql('UPDATE option SET type = \'int\' WHERE option_id = 17');
    }
}
