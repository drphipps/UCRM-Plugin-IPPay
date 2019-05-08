<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160503181259 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE email_log_log_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE email_log (log_id INT NOT NULL, user_id INT DEFAULT NULL, client_id INT DEFAULT NULL, invoice_id INT DEFAULT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, message TEXT NOT NULL, script VARCHAR(100) DEFAULT NULL, status INT NOT NULL, sender VARCHAR(320) NOT NULL, recipient VARCHAR(320) NOT NULL, subject VARCHAR(255) DEFAULT NULL, body TEXT DEFAULT NULL, attachments TEXT DEFAULT NULL, failed_recipients TEXT DEFAULT NULL, PRIMARY KEY(log_id))');
        $this->addSql('CREATE INDEX IDX_6FB4883A76ED395 ON email_log (user_id)');
        $this->addSql('CREATE INDEX IDX_6FB488319EB6921 ON email_log (client_id)');
        $this->addSql('CREATE INDEX IDX_6FB48832989F1FD ON email_log (invoice_id)');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT FK_6FB4883A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT FK_6FB488319EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT FK_6FB48832989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (invoice_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE email_log_log_id_seq CASCADE');
        $this->addSql('DROP TABLE email_log');
    }
}
