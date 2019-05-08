<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170809073646 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE ticket_comment_attachment (id SERIAL NOT NULL, ticket_comment_id INT DEFAULT NULL, filename TEXT NOT NULL, original_filename TEXT NOT NULL, mime_type TEXT NOT NULL, size INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_97317FEF6EFAEF47 ON ticket_comment_attachment (ticket_comment_id)');
        $this->addSql('ALTER TABLE ticket_comment_attachment ADD CONSTRAINT FK_97317FEF6EFAEF47 FOREIGN KEY (ticket_comment_id) REFERENCES ticket_comment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE ticket_comment_attachment');
    }
}
