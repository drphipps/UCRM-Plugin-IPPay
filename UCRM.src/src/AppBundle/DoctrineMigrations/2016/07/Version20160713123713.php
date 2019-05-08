<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160713123713 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE option SET position = 9 WHERE option_id = 31');
        $this->addSql('INSERT INTO option (option_id, category_id, position, code, name, description, type, value, choice_type_options) VALUES (32, 1, 8, \'PDF_PAGE_SIZE\', \'PDF page size\', \'\', \'choice\', \'letter\', \'{"choices":{"letter":"US letter","legal":"US legal","A4":"A4"}}\')');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE option_id = 32');
        $this->addSql('UPDATE option SET position = 8 WHERE option_id = 31');
    }
}
