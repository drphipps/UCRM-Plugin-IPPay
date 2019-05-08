<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160808073702 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('COMMENT ON COLUMN email_log.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN entity_log.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN client_log.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN payment.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN payment_plan.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN payment_plan.canceled_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN refund.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN fee.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN invoice.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN invoice.due_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN "user".password_requested_at IS \'Datetime of user\'\'s request for reset password(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN device_log.created_date IS \'(DC2Type:datetime_utc)\'');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('COMMENT ON COLUMN fee.created_date IS NULL');
        $this->addSql('COMMENT ON COLUMN payment.created_date IS NULL');
        $this->addSql('COMMENT ON COLUMN invoice.created_date IS NULL');
        $this->addSql('COMMENT ON COLUMN invoice.due_date IS NULL');
        $this->addSql('COMMENT ON COLUMN "user".password_requested_at IS \'Datetime of user\'\'s request for reset password\'');
        $this->addSql('COMMENT ON COLUMN refund.created_date IS NULL');
        $this->addSql('COMMENT ON COLUMN device_log.created_date IS NULL');
        $this->addSql('COMMENT ON COLUMN email_log.created_date IS NULL');
        $this->addSql('COMMENT ON COLUMN payment_plan.created_date IS NULL');
        $this->addSql('COMMENT ON COLUMN payment_plan.canceled_date IS NULL');
        $this->addSql('COMMENT ON COLUMN entity_log.created_date IS NULL');
        $this->addSql('COMMENT ON COLUMN client_log.created_date IS NULL');
    }
}
