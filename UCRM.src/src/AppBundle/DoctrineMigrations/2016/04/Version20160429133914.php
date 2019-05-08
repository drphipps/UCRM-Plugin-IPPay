<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160429133914 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE invoice_approve_draft (invoice_id INT NOT NULL, PRIMARY KEY(invoice_id))');
        $this->addSql('ALTER TABLE invoice_approve_draft ADD CONSTRAINT FK_EEFFDF7A2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (invoice_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE invoice_send_email (invoice_id INT NOT NULL, PRIMARY KEY(invoice_id))');
        $this->addSql('ALTER TABLE invoice_send_email ADD CONSTRAINT FK_772C09AC2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (invoice_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE invoice_send_email');
        $this->addSql('DROP TABLE invoice_approve_draft');
    }
}
