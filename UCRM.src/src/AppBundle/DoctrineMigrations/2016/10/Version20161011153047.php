<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161011153047 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client_log DROP CONSTRAINT FK_A89BFB61A76ED395');
        $this->addSql('ALTER TABLE client_log ADD CONSTRAINT FK_A89BFB61A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entity_log DROP CONSTRAINT FK_F1B00862A76ED395');
        $this->addSql('ALTER TABLE entity_log ADD CONSTRAINT FK_F1B00862A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ALTER email1 DROP NOT NULL');
        $this->addSql('COMMENT ON COLUMN "user".deleted_at IS \'(DC2Type:datetime_utc)\'');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE "user" DROP deleted_at');
        $this->addSql('ALTER TABLE "user" ALTER email1 SET NOT NULL');
        $this->addSql('ALTER TABLE entity_log DROP CONSTRAINT fk_f1b00862a76ed395');
        $this->addSql('ALTER TABLE entity_log ADD CONSTRAINT fk_f1b00862a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE client_log DROP CONSTRAINT fk_a89bfb61a76ed395');
        $this->addSql('ALTER TABLE client_log ADD CONSTRAINT fk_a89bfb61a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
