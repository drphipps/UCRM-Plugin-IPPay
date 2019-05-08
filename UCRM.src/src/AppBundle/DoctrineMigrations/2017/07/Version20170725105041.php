<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170725105041 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket_comment RENAME TO ticket_activity');
        $this->addSql('ALTER TABLE ticket_activity ADD dtype VARCHAR(255) NOT NULL DEFAULT \'ticketcomment\'');
        $this->addSql('ALTER TABLE ticket_activity ALTER dtype DROP DEFAULT');
        $this->addSql('CREATE TABLE ticket_comment (id INT NOT NULL, body TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO ticket_comment (id, body) SELECT id, body FROM ticket_activity');
        $this->addSql('ALTER TABLE ticket_activity DROP body');
        $this->addSql('DROP SEQUENCE ticket_comment_id_seq CASCADE');
        $this->addSql('ALTER INDEX idx_98b80b3e700047d2 RENAME TO IDX_291DF5DB700047D2');
        $this->addSql('ALTER INDEX idx_98b80b3ea76ed395 RENAME TO IDX_291DF5DBA76ED395');
        $this->addSql('ALTER TABLE ticket_comment ADD CONSTRAINT FK_98B80B3EBF396750 FOREIGN KEY (id) REFERENCES ticket_activity (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE SEQUENCE ticket_activity_id_seq');
        $this->addSql('SELECT setval(\'ticket_activity_id_seq\', (SELECT MAX(id) FROM ticket_activity))');
        $this->addSql('ALTER TABLE ticket_activity ALTER id SET DEFAULT nextval(\'ticket_activity_id_seq\')');
        $this->addSql('CREATE TABLE ticket_assignment (id INT NOT NULL, assigned_user_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A656D6EEADF66B1A ON ticket_assignment (assigned_user_id)');
        $this->addSql('ALTER TABLE ticket_assignment ADD CONSTRAINT FK_A656D6EEADF66B1A FOREIGN KEY (assigned_user_id) REFERENCES "user" (user_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_assignment ADD CONSTRAINT FK_A656D6EEBF396750 FOREIGN KEY (id) REFERENCES ticket_activity (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'Not implemented.');
    }
}
