<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170907113900 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket_comment_attachment DROP CONSTRAINT FK_97317FEF6EFAEF47');
        $this->addSql('ALTER TABLE ticket_comment_attachment ADD CONSTRAINT FK_97317FEF6EFAEF47 FOREIGN KEY (ticket_comment_id) REFERENCES ticket_comment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket_comment_attachment DROP CONSTRAINT FK_97317fef6efaef47');
        $this->addSql('ALTER TABLE ticket_comment_attachment ADD CONSTRAINT FK_97317fef6efaef47 FOREIGN KEY (ticket_comment_id) REFERENCES ticket_comment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
