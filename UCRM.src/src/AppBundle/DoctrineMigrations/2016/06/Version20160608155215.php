<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160608155215 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE option SET position = 4 WHERE option_id = 26');
        $this->addSql('UPDATE option SET position = 5 WHERE option_id = 20');
        $this->addSql('UPDATE option SET position = 6 WHERE option_id = 14');
        $this->addSql('UPDATE option SET position = 7 WHERE option_id = 1');
        $this->addSql('INSERT INTO option (option_id, category_id, position, code, name, description, type) VALUES (29, 1, 2, \'SERVER_PORT\', \'Server port\', \'UCRM Server port. For example 8080, 80, etc.\', \'string\')');
        $this->addSql('INSERT INTO option (option_id, category_id, position, code, name, description, type) VALUES (30, 1, 3, \'SERVER_SUSPEND_PORT\', \'Server suspend port\', \'UCRM Server suspend port. For example 8081, 81, etc.\', \'string\')');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE option_id IN (29, 30)');
        $this->addSql('UPDATE option SET position = 2 WHERE option_id = 26');
        $this->addSql('UPDATE option SET position = 3 WHERE option_id = 20');
        $this->addSql('UPDATE option SET position = 4 WHERE option_id = 14');
        $this->addSql('UPDATE option SET position = 5 WHERE option_id = 1');
    }
}
