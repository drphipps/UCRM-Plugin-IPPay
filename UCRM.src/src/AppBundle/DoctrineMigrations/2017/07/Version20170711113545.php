<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170711113545 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE mailing (id SERIAL NOT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, filter_organization JSON DEFAULT NULL, filter_client_type JSON DEFAULT NULL, filter_client_tag JSON DEFAULT NULL, filter_tariff JSON DEFAULT NULL, filter_period_start_day JSON DEFAULT NULL, filter_site JSON DEFAULT NULL, filter_device JSON DEFAULT NULL, message TEXT DEFAULT NULL, subject TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN mailing.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE email_log ADD bulk_mail_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT FK_6FB4883FC3960DB FOREIGN KEY (bulk_mail_id) REFERENCES mailing (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6FB4883FC3960DB ON email_log (bulk_mail_id)');
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (52, 1, \'AppBundle\Controller\MailingController\', \'edit\')');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log DROP CONSTRAINT FK_6FB4883FC3960DB');
        $this->addSql('DROP TABLE mailing');
        $this->addSql('DROP INDEX IDX_6FB4883FC3960DB');
        $this->addSql('ALTER TABLE email_log DROP bulk_mail_id');
        $this->addSql('DELETE FROM user_group_permission WHERE permission_id = 52');
    }
}
