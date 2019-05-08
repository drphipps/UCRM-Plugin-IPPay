<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171025143724 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE shortcut (id SERIAL NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, route VARCHAR(255) NOT NULL, parameters JSON NOT NULL, suffix VARCHAR(255) DEFAULT NULL, sequence INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2EF83F9CA76ED395 ON shortcut (user_id)');
        $this->addSql('COMMENT ON COLUMN shortcut.parameters IS \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE shortcut ADD CONSTRAINT FK_2EF83F9CA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE shortcut');
    }
}
