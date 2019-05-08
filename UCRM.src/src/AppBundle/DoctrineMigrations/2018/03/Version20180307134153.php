<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180307134153 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE payment_plan ADD service_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_plan ADD CONSTRAINT FK_FCD9CC09ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (service_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_FCD9CC09ED5CA9E6 ON payment_plan (service_id)');
        $this->addSql('INSERT INTO option (code, value) VALUES (\'NOTIFICATION_SUBSCRIPTION_AMOUNT_CHANGED\', 1)');
        $this->addSql(
            sprintf(
                'INSERT INTO notification_template (template_id, subject, body, type) VALUES (29, \'Subscription amount changed\', \'%s\', 29)',
                '<p>Dear %CLIENT_NAME%! Your subscription amount has been changed from %PAYMENT_PLAN_OLD_AMOUNT% to %PAYMENT_PLAN_NEW_AMOUNT%.</p>'
            )
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE code = \'NOTIFICATION_SUBSCRIPTION_AMOUNT_CHANGED\'');
        $this->addSql('DELETE FROM notification_template WHERE template_id = 29');
        $this->addSql('ALTER TABLE payment_plan DROP CONSTRAINT FK_FCD9CC09ED5CA9E6');
        $this->addSql('DROP INDEX IDX_FCD9CC09ED5CA9E6');
        $this->addSql('ALTER TABLE payment_plan DROP service_id');
    }
}
