<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160624095528 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE client_log_log_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE client_log (log_id INT NOT NULL, user_id INT DEFAULT NULL, client_id INT DEFAULT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, message TEXT NOT NULL, PRIMARY KEY(log_id))');
        $this->addSql('CREATE INDEX IDX_A89BFB61A76ED395 ON client_log (user_id)');
        $this->addSql('CREATE INDEX IDX_A89BFB6119EB6921 ON client_log (client_id)');
        $this->addSql('ALTER TABLE client_log ADD CONSTRAINT FK_A89BFB61A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE client_log ADD CONSTRAINT FK_A89BFB6119EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE client_log_log_id_seq CASCADE');
        $this->addSql('DROP TABLE client_log');
    }
}
