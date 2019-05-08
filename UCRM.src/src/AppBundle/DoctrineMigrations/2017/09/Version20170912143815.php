<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170912143815 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket_comment_mail_attachment DROP CONSTRAINT FK_8ABB24DD6EFAEF47');
        $this->addSql('ALTER TABLE ticket_comment_mail_attachment ADD CONSTRAINT FK_8ABB24DD6EFAEF47 FOREIGN KEY (ticket_comment_id) REFERENCES ticket_comment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket_comment_mail_attachment DROP CONSTRAINT fk_8abb24dd6efaef47');
        $this->addSql('ALTER TABLE ticket_comment_mail_attachment ADD CONSTRAINT fk_8abb24dd6efaef47 FOREIGN KEY (ticket_comment_id) REFERENCES ticket_comment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
