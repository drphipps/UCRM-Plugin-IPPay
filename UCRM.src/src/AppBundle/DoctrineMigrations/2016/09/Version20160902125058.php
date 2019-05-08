<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160902125058 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE service_device_outage_service_device_outage_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE service_device_outage (service_device_outage_id INT NOT NULL, service_device_id INT NOT NULL, outage_start TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, outage_end TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(service_device_outage_id))');
        $this->addSql('CREATE INDEX IDX_C0D14661CC35FD9E ON service_device_outage (service_device_id)');
        $this->addSql('COMMENT ON COLUMN service_device_outage.outage_start IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN service_device_outage.outage_end IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE service_device_outage ADD CONSTRAINT FK_C0D14661CC35FD9E FOREIGN KEY (service_device_id) REFERENCES service_device (service_device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_device ADD ping_notification_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_device ADD ping_error_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE service_device ADD ping_notification_sent TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE service_device ADD status INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE service_device ADD send_ping_notifications BOOLEAN DEFAULT \'true\' NOT NULL');
        $this->addSql('COMMENT ON COLUMN service_device.ping_notification_sent IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE service_device ADD CONSTRAINT FK_37E8B3B8B21818BE FOREIGN KEY (ping_notification_user_id) REFERENCES "user" (user_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_37E8B3B8B21818BE ON service_device (ping_notification_user_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE service_device_outage_service_device_outage_id_seq CASCADE');
        $this->addSql('DROP TABLE service_device_outage');
        $this->addSql('ALTER TABLE service_device DROP CONSTRAINT FK_37E8B3B8B21818BE');
        $this->addSql('DROP INDEX IDX_37E8B3B8B21818BE');
        $this->addSql('ALTER TABLE service_device DROP ping_notification_user_id');
        $this->addSql('ALTER TABLE service_device DROP ping_error_count');
        $this->addSql('ALTER TABLE service_device DROP ping_notification_sent');
        $this->addSql('ALTER TABLE service_device DROP status');
        $this->addSql('ALTER TABLE service_device DROP send_ping_notifications');
    }
}
