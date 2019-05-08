<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160601103222 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE entity_log ADD user_type INT DEFAULT NULL');
        $this->addSql('ALTER TABLE entity_log ALTER change_type DROP NOT NULL');
        $this->addSql('ALTER TABLE entity_log ALTER entity DROP NOT NULL');
        $this->addSql('ALTER TABLE entity_log ALTER entity_id DROP NOT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE entity_log DROP user_type');
        $this->addSql('ALTER TABLE entity_log ALTER change_type SET NOT NULL');
        $this->addSql('ALTER TABLE entity_log ALTER entity SET NOT NULL');
        $this->addSql('ALTER TABLE entity_log ALTER entity_id SET NOT NULL');
    }
}
