<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161012103706 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client ADD anet_customer_payment_profile_id VARCHAR(100) DEFAULT NULL');
        $this->addSql('INSERT INTO notification_template (template_id, subject, body, type) VALUES (12, \'PayPal subscription cancelled\', \'<p>Dear %CLIENT_FIRST_NAME%! Your PayPal subscription has been cancelled.</p>\', 12)');
        $this->addSql('INSERT INTO notification_template (template_id, subject, body, type) VALUES (13, \'Authorize.Net subscription cancelled\', \'<p>Dear %CLIENT_FIRST_NAME%! Your Authorize.Net subscription has been cancelled.</p>\', 13)');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client DROP anet_customer_payment_profile_id');
        $this->addSql('DELETE FROM notification_template WHERE template_id IN (12, 13)');
    }
}
