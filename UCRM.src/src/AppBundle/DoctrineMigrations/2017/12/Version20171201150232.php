<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171201150232 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE quote ADD email_sent_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE email_log ADD quote_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT FK_6FB4883DB805178 FOREIGN KEY (quote_id) REFERENCES quote (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6FB4883DB805178 ON email_log (quote_id)');
        $this->addSql(
            sprintf(
                'INSERT INTO notification_template (template_id, subject, body, type) VALUES (28, \'New quote\', \'%s\', 28)',
                '<p>Dear %CLIENT_NAME%! We are sending you new quote.</p>'
            )
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log DROP CONSTRAINT FK_6FB4883DB805178');
        $this->addSql('DROP INDEX IDX_6FB4883DB805178');
        $this->addSql('ALTER TABLE email_log DROP quote_id');
        $this->addSql('ALTER TABLE quote DROP email_sent_date');
        $this->addSql('DELETE FROM notification_template WHERE template_id = 28');
    }
}
