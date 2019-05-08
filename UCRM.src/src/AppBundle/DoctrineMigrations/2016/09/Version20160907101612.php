<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160907101612 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('UPDATE option SET position = position + 1 WHERE category_id = 1 AND position > 7');
        $this->addSql('INSERT INTO option (option_id, position, code, name, description, type, value, category_id) VALUES (41, 8, \'GOOGLE_API_KEY\', \'Google API key\', \'If set, Google Maps will be used instead of MapBox.com to display maps and geocode addresses. For more information on how to set up Google Maps API see <a href="#" class="help-panel" data-help-section="google-api">help</a>.\', \'string\', NULL, 1)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE option_id = 41');
        $this->addSql('UPDATE option SET position = position - 1 WHERE category_id = 1 AND position > 7');
    }
}
