<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160919124208 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('UPDATE option SET position = position + 1 WHERE category_id = 5');
        $this->addSql('INSERT INTO option (option_id, position, code, name, description, type, value, category_id, value_txt, choice_type_options) VALUES (45, 0, \'PING_OUTAGE_THRESHOLD\', \'Outage threshold (packet loss %)\', \'Device outages will be detected when packet loss percentage reaches at least this value.\', \'int\', \'15\', 5, NULL, NULL)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE option_id = 45');
        $this->addSql('UPDATE option SET position = position - 1 WHERE category_id = 5');
    }
}
