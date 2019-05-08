<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170201140230 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log ADD original_recipient VARCHAR(320) DEFAULT NULL');
        $this->addSql('ALTER TABLE email_log ADD sent_in_sandbox BOOLEAN DEFAULT \'false\' NOT NULL');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log DROP original_recipient');
        $this->addSql('ALTER TABLE email_log DROP sent_in_sandbox');
    }
}
