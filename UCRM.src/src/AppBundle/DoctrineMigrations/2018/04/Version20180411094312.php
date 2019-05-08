<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180411094312 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql("INSERT INTO option(code, value) VALUES ('MAILER_VERIFY_SSL_CERTIFICATES', 1)");
        $this->addSql('ALTER TABLE ticket_imap_inbox ADD verify_ssl_certificate BOOLEAN DEFAULT \'true\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE code = \'MAILER_VERIFY_SSL_CERTIFICATES\'');
        $this->addSql('ALTER TABLE ticket_imap_inbox DROP verify_ssl_certificate');
    }
}
