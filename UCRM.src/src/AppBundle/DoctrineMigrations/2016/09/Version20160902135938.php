<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160902135938 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE service_ip ADD was_last_connection_successful BOOLEAN DEFAULT \'false\' NOT NULL');

        $this->addSql('ALTER TABLE device ALTER ssh_port SET DEFAULT 22');
        $this->addSql('ALTER TABLE service_device ADD model_name VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE service_device ADD os_version VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE service_device ADD backup_hash TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_device ADD last_backup_timestamp TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN service_device.last_backup_timestamp IS \'(DC2Type:datetime_utc)\'');

        $this->addSql('CREATE SEQUENCE service_device_log_log_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE service_device_log (log_id INT NOT NULL, service_device_id INT DEFAULT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, message TEXT DEFAULT NULL, script VARCHAR(100) DEFAULT NULL, status INT DEFAULT 0 NOT NULL, PRIMARY KEY(log_id))');
        $this->addSql('CREATE INDEX IDX_F3908BE8CC35FD9E ON service_device_log (service_device_id)');
        $this->addSql('COMMENT ON COLUMN service_device_log.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE service_device_log ADD CONSTRAINT FK_F3908BE8CC35FD9E FOREIGN KEY (service_device_id) REFERENCES service_device (service_device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE device_log ALTER status SET DEFAULT 0');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_ip DROP was_last_connection_successful');

        $this->addSql('ALTER TABLE service_device DROP model_name');
        $this->addSql('ALTER TABLE service_device DROP os_version');
        $this->addSql('ALTER TABLE service_device DROP backup_hash');
        $this->addSql('ALTER TABLE service_device DROP last_backup_timestamp');
        $this->addSql('ALTER TABLE device ALTER ssh_port DROP DEFAULT');

        $this->addSql('DROP SEQUENCE service_device_log_log_id_seq CASCADE');
        $this->addSql('DROP TABLE service_device_log');
        $this->addSql('ALTER TABLE device_log ALTER status DROP DEFAULT');
    }
}
