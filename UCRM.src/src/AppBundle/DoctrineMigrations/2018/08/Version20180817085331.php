<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180817085331 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log DROP CONSTRAINT FK_6FB48833F29C9EC');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT FK_6FB48833F29C9EC FOREIGN KEY (resent_email_log_id) REFERENCES email_log (log_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log DROP CONSTRAINT fk_6fb48833f29c9ec');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT fk_6fb48833f29c9ec FOREIGN KEY (resent_email_log_id) REFERENCES email_log (log_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
