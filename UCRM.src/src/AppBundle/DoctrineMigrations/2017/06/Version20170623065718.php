<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170623065718 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE ticket (id SERIAL NOT NULL, client_id INT DEFAULT NULL, assigned_user_id INT DEFAULT NULL, subject VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status SMALLINT DEFAULT 0 NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_97A0ADA319EB6921 ON ticket (client_id)');
        $this->addSql('CREATE INDEX IDX_97A0ADA3ADF66B1A ON ticket (assigned_user_id)');
        $this->addSql('COMMENT ON COLUMN ticket.created_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('CREATE TABLE ticket_comment (id SERIAL NOT NULL, ticket_id INT NOT NULL, user_id INT DEFAULT NULL, body TEXT NOT NULL, public BOOLEAN DEFAULT \'true\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_98B80B3E700047D2 ON ticket_comment (ticket_id)');
        $this->addSql('CREATE INDEX IDX_98B80B3EA76ED395 ON ticket_comment (user_id)');
        $this->addSql('COMMENT ON COLUMN ticket_comment.created_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA319EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3ADF66B1A FOREIGN KEY (assigned_user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_comment ADD CONSTRAINT FK_98B80B3E700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_comment ADD CONSTRAINT FK_98B80B3EA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket_comment DROP CONSTRAINT FK_98B80B3E700047D2');
        $this->addSql('DROP TABLE ticket');
        $this->addSql('DROP TABLE ticket_comment');
    }
}
