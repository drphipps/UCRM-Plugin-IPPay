<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180523121238 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log RENAME COLUMN resent_message_id TO resent_email_log_id');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT FK_6FB48833F29C9EC FOREIGN KEY (resent_email_log_id) REFERENCES email_log (log_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6FB48833F29C9EC ON email_log (resent_email_log_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log DROP CONSTRAINT FK_6FB48833F29C9EC');
        $this->addSql('DROP INDEX UNIQ_6FB48833F29C9EC');
        $this->addSql('ALTER TABLE email_log RENAME COLUMN resent_email_log_id TO resent_message_id');
    }
}
