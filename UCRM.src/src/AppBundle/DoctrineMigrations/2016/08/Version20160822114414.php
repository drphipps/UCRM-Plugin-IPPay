<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160822114414 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE device_outage_device_outage_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE device_outage (device_outage_id INT NOT NULL, device_id INT NOT NULL, outage_start TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, outage_end TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(device_outage_id))');
        $this->addSql('CREATE INDEX IDX_106EFCE394A4C7D4 ON device_outage (device_id)');
        $this->addSql('COMMENT ON COLUMN device_outage.outage_start IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN device_outage.outage_end IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE device_outage ADD CONSTRAINT FK_106EFCE394A4C7D4 FOREIGN KEY (device_id) REFERENCES device (device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE device ADD ping_error_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE device ADD ping_notification_sent TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE device ADD status INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE device ADD send_ping_notifications BOOLEAN DEFAULT \'true\' NOT NULL');
        $this->addSql('COMMENT ON COLUMN device.ping_notification_sent IS \'(DC2Type:datetime_utc)\'');

        $this->addSql('INSERT INTO setting_category (category_id, name) VALUES (5, \'ping\')');
        $this->addSql('INSERT INTO option (option_id, category_id, position, code, name, description, type, value, choice_type_options) VALUES (36, 5, 0, \'NOTIFICATION_PING_USER\', \'Send notifications to\', \'If no global user is selected, notifications will only be sent in case user is selected directly on device.\', \'admin\', NULL, null)');
        $this->addSql('INSERT INTO option (option_id, category_id, position, code, name, description, type, value, choice_type_options) VALUES (37, 5, 1, \'NOTIFICATION_PING_DOWN\', \'DOWN notifications\', NULL, \'toggle\', \'1\', null)');
        $this->addSql('INSERT INTO option (option_id, category_id, position, code, name, description, type, value, choice_type_options) VALUES (38, 5, 2, \'NOTIFICATION_PING_UNREACHABLE\', \'UNREACHABLE notifications\', NULL, \'toggle\', \'1\', null)');
        $this->addSql('INSERT INTO option (option_id, category_id, position, code, name, description, type, value, choice_type_options) VALUES (39, 5, 3, \'NOTIFICATION_PING_REPAIRED\', \'REPAIRED notifications\', NULL, \'toggle\', \'1\', null)');

        $this->addSql('ALTER TABLE device ADD ping_notification_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE device ADD CONSTRAINT FK_92FB68EB21818BE FOREIGN KEY (ping_notification_user_id) REFERENCES "user" (user_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_92FB68EB21818BE ON device (ping_notification_user_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE device_outage_device_outage_id_seq CASCADE');
        $this->addSql('DROP TABLE device_outage');
        $this->addSql('ALTER TABLE device DROP ping_error_count');
        $this->addSql('ALTER TABLE device DROP ping_notification_sent');
        $this->addSql('ALTER TABLE device DROP status');
        $this->addSql('ALTER TABLE device DROP send_ping_notifications');

        $this->addSql('DELETE FROM option WHERE option_id IN (36, 37, 38, 39)');
        $this->addSql('DELETE FROM setting_category WHERE category_id = 5');

        $this->addSql('ALTER TABLE device DROP CONSTRAINT FK_92FB68EB21818BE');
        $this->addSql('DROP INDEX IDX_92FB68EB21818BE');
        $this->addSql('ALTER TABLE device DROP ping_notification_user_id');
    }
}
