<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180517140103 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE ticket_comment_mail_removed (id SERIAL NOT NULL, inbox_id INT DEFAULT NULL, email_id VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_85B30BF118DA89DD ON ticket_comment_mail_removed (inbox_id)');
        $this->addSql('ALTER TABLE ticket_comment_mail_removed ADD CONSTRAINT FK_85B30BF118DA89DD FOREIGN KEY (inbox_id) REFERENCES ticket_imap_inbox (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE ticket_comment_mail_removed');
    }
}
