<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160526120118 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO option (option_id, position, code, name, description, type, value, category_id, value_txt, choice_type_options) VALUES (26, 2, \'SERVER_FQDN\', \'Server domain name\', \'Domain name of the application (e.g. <em>ucrm.ubnt.com</em>). Used for email link generation and online payments.\', \'string\', NULL, \'1\', NULL, NULL)');
        $this->addSql('UPDATE option SET position = 3 WHERE option_id = 20');
        $this->addSql('UPDATE option SET position = 4 WHERE option_id = 14');
        $this->addSql('UPDATE option SET position = 5 WHERE option_id = 1');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE option_id = 26');
        $this->addSql('UPDATE option SET position = 2 WHERE option_id = 20');
        $this->addSql('UPDATE option SET position = 3 WHERE option_id = 14');
        $this->addSql('UPDATE option SET position = 4 WHERE option_id = 1');
    }
}
