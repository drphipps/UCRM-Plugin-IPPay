<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160518163943 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log DROP CONSTRAINT FK_6FB48832989F1FD');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT FK_6FB48832989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (invoice_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entity_log DROP CONSTRAINT FK_F1B0086219EB6921');
        $this->addSql('ALTER TABLE entity_log DROP CONSTRAINT FK_F1B00862F6BD1646');
        $this->addSql('ALTER TABLE entity_log ADD CONSTRAINT FK_F1B0086219EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entity_log ADD CONSTRAINT FK_F1B00862F6BD1646 FOREIGN KEY (site_id) REFERENCES site (site_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log DROP CONSTRAINT fk_6fb48832989f1fd');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT fk_6fb48832989f1fd FOREIGN KEY (invoice_id) REFERENCES invoice (invoice_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entity_log DROP CONSTRAINT fk_f1b0086219eb6921');
        $this->addSql('ALTER TABLE entity_log DROP CONSTRAINT fk_f1b00862f6bd1646');
        $this->addSql('ALTER TABLE entity_log ADD CONSTRAINT fk_f1b0086219eb6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entity_log ADD CONSTRAINT fk_f1b00862f6bd1646 FOREIGN KEY (site_id) REFERENCES site (site_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
