<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170627073317 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment_plan ADD next_payment_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_plan ADD failures SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('COMMENT ON COLUMN payment_plan.next_payment_date IS \'(DC2Type:datetime_utc)\'');

        $this->addSql('INSERT INTO notification_template (template_id, subject, body, type) VALUES (18, \'IPPay subscription cancelled\', \'<p>Dear %CLIENT_NAME%! Your IPPay subscription has been cancelled.</p>\', 18)');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment_plan DROP next_payment_date');
        $this->addSql('ALTER TABLE payment_plan DROP failures');
    }
}
