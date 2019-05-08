<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160516185201 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE entity_log_log_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE entity_log (log_id INT NOT NULL, user_id INT DEFAULT NULL, client_id INT DEFAULT NULL, site_id INT DEFAULT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, log TEXT DEFAULT NULL, change_type INT NOT NULL, entity VARCHAR(255) NOT NULL, entity_id INT NOT NULL, parent_entity VARCHAR(255) DEFAULT NULL, parent_entity_id INT DEFAULT NULL, PRIMARY KEY(log_id))');
        $this->addSql('CREATE INDEX IDX_F1B00862A76ED395 ON entity_log (user_id)');
        $this->addSql('CREATE INDEX IDX_F1B0086219EB6921 ON entity_log (client_id)');
        $this->addSql('CREATE INDEX IDX_F1B00862F6BD1646 ON entity_log (site_id)');
        $this->addSql('CREATE INDEX date_idx ON entity_log (created_date)');
        $this->addSql('ALTER TABLE entity_log ADD CONSTRAINT FK_F1B00862A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entity_log ADD CONSTRAINT FK_F1B0086219EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entity_log ADD CONSTRAINT FK_F1B00862F6BD1646 FOREIGN KEY (site_id) REFERENCES site (site_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE entity_log_log_id_seq CASCADE');
        $this->addSql('DROP TABLE entity_log');
    }
}
