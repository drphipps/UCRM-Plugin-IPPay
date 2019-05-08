<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160822084544 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE device_relations (parent_id INT NOT NULL, child_id INT NOT NULL, PRIMARY KEY(child_id, parent_id))');
        $this->addSql('CREATE INDEX IDX_2F8CABE9727ACA70 ON device_relations (parent_id)');
        $this->addSql('CREATE INDEX IDX_2F8CABE9DD62C21B ON device_relations (child_id)');
        $this->addSql('ALTER TABLE device_relations ADD CONSTRAINT FK_2F8CABE9727ACA70 FOREIGN KEY (parent_id) REFERENCES device (device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE device_relations ADD CONSTRAINT FK_2F8CABE9DD62C21B FOREIGN KEY (child_id) REFERENCES device (device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE device_relations');
    }
}
