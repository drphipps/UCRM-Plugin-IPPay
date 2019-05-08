<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161109110140 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log ADD message_id VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6FB4883537A1329 ON email_log (message_id)');
        $this->addSql('ALTER TABLE email_log ALTER message DROP NOT NULL');
        $this->addSql('ALTER TABLE email_log ALTER status DROP NOT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log DROP message_id');
        $this->addSql('DROP INDEX UNIQ_6FB4883537A1329');
        $this->addSql('ALTER TABLE email_log ALTER message SET NOT NULL');
        $this->addSql('ALTER TABLE email_log ALTER status SET NOT NULL');
    }
}
