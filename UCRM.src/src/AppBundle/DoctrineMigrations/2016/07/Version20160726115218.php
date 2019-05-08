<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160726115218 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device_interface ADD encryption_key_wpa2 VARCHAR(256) DEFAULT NULL');
        $this->addSql('ALTER TABLE device_interface ADD internal_id VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE device_interface ADD internal_name VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE device_interface ADD internal_type VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE device_interface ADD mtu INT DEFAULT NULL');
        $this->addSql('ALTER TABLE device_interface ADD interface_model VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE device_interface ADD band VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE device_interface ADD channel_width INT DEFAULT NULL');
        $this->addSql('ALTER TABLE device_interface ADD wireless_protocol VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE device_interface RENAME COLUMN encryption_key TO encryption_key_wpa');
        $this->addSql('ALTER TABLE device_interface ALTER frequency TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE device_interface ALTER frequency DROP DEFAULT');
        $this->addSql('ALTER TABLE device_interface_ip ADD internal_id VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE device_interface ALTER encryption_key_wpa TYPE VARCHAR(256)');
        $this->addSql('ALTER TABLE device_interface_ip ADD was_last_connection_successful BOOLEAN DEFAULT \'false\' NOT NULL');

        $this->addSql('CREATE SEQUENCE service_device_service_device_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE service_device (service_device_id INT NOT NULL, interface_id INT NOT NULL, mac_address VARCHAR(17) NOT NULL, rx_rate VARCHAR(32) DEFAULT NULL, tx_rate VARCHAR(32) DEFAULT NULL, uptime VARCHAR(32) DEFAULT NULL, last_activity VARCHAR(32) DEFAULT NULL, signal_strength VARCHAR(32) DEFAULT NULL, signal_to_noice VARCHAR(32) DEFAULT NULL, tx_ccq INT DEFAULT NULL, last_ip VARCHAR(32) DEFAULT NULL, first_seen TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_seen TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(service_device_id))');
        $this->addSql('CREATE INDEX IDX_5C89FD3DAB0BE982 ON service_device (interface_id)');
        $this->addSql('CREATE INDEX service_device_mac_address_idx ON service_device (mac_address)');
        $this->addSql('ALTER TABLE service_device ADD CONSTRAINT FK_5C89FD3DAB0BE982 FOREIGN KEY (interface_id) REFERENCES device_interface (interface_id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE device_interface ADD mode INT DEFAULT NULL');
        $this->addSql('ALTER TABLE device_interface ADD unicast_ciphers INT DEFAULT NULL');
        $this->addSql('ALTER TABLE device_interface ADD group_ciphers INT DEFAULT NULL');
        $this->addSql('ALTER TABLE device_interface ADD encryption_mode INT DEFAULT NULL');

        $this->addSql('UPDATE vendor SET name = \'Ubiquiti Networks EdgeOS\' WHERE vendor_id = 1');
        $this->addSql('INSERT INTO vendor (vendor_id, name) VALUES (3, \'Ubiquiti Networks airOS\')');

        $this->addSql('ALTER TABLE device ADD ssh_port INT DEFAULT NULL');
        $this->addSql('ALTER TABLE device ALTER os_version TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE device ADD backup_hash TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE device ADD last_backup_timestamp TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN device.last_backup_timestamp IS \'(DC2Type:datetime_utc)\'');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device_interface_ip DROP internal_id');
        $this->addSql('ALTER TABLE device_interface ADD encryption_key VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE device_interface DROP encryption_key_wpa');
        $this->addSql('ALTER TABLE device_interface DROP encryption_key_wpa2');
        $this->addSql('ALTER TABLE device_interface DROP internal_id');
        $this->addSql('ALTER TABLE device_interface DROP internal_name');
        $this->addSql('ALTER TABLE device_interface DROP internal_type');
        $this->addSql('ALTER TABLE device_interface DROP mtu');
        $this->addSql('ALTER TABLE device_interface DROP interface_model');
        $this->addSql('ALTER TABLE device_interface DROP band');
        $this->addSql('ALTER TABLE device_interface DROP channel_width');
        $this->addSql('ALTER TABLE device_interface DROP wireless_protocol');
        $this->addSql('ALTER TABLE device_interface ALTER frequency TYPE INT');
        $this->addSql('ALTER TABLE device_interface ALTER frequency DROP DEFAULT');
        $this->addSql('ALTER TABLE device_interface_ip DROP was_last_connection_successful');

        $this->addSql('DROP SEQUENCE service_device_service_device_id_seq CASCADE');
        $this->addSql('DROP TABLE service_device');

        $this->addSql('ALTER TABLE device_interface DROP mode');
        $this->addSql('ALTER TABLE device_interface DROP unicast_ciphers');
        $this->addSql('ALTER TABLE device_interface DROP group_ciphers');
        $this->addSql('ALTER TABLE device_interface DROP encryption_mode');

        $this->addSql('UPDATE device SET vendor_id = 1 WHERE vendor_id = 3');

        $this->addSql('UPDATE vendor SET name = \'Ubiquiti Networks\' WHERE vendor_id = 1');
        $this->addSql('DELETE FROM vendor WHERE vendor_id = 3');

        $this->addSql('ALTER TABLE device DROP ssh_port');
        $this->addSql('ALTER TABLE device DROP backup_hash');
        $this->addSql('ALTER TABLE device DROP last_backup_timestamp');
    }
}
