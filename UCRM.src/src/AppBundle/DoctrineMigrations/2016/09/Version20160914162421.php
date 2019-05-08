<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160914162421 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE wireless_statistics_long_term (id SERIAL NOT NULL, device_id INT NOT NULL, time DATE NOT NULL, ccq INT DEFAULT NULL, rx_rate INT DEFAULT NULL, tx_rate INT DEFAULT NULL, signal INT DEFAULT NULL, remote_signal INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7A12C76394A4C7D4 ON wireless_statistics_long_term (device_id)');
        $this->addSql('CREATE TABLE wireless_statistics_service_long_term (id SERIAL NOT NULL, service_device_id INT NOT NULL, time DATE NOT NULL, ccq INT DEFAULT NULL, rx_rate INT DEFAULT NULL, tx_rate INT DEFAULT NULL, signal INT DEFAULT NULL, remote_signal INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9BB5099BCC35FD9E ON wireless_statistics_service_long_term (service_device_id)');
        $this->addSql('CREATE TABLE wireless_statistics_service_short_term (id SERIAL NOT NULL, service_device_id INT NOT NULL, time TIMESTAMP(0) WITH TIME ZONE NOT NULL, ccq INT DEFAULT NULL, rx_rate INT DEFAULT NULL, tx_rate INT DEFAULT NULL, signal INT DEFAULT NULL, remote_signal INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2A236987CC35FD9E ON wireless_statistics_service_short_term (service_device_id)');
        $this->addSql('CREATE TABLE wireless_statistics_short_term (id SERIAL NOT NULL, device_id INT NOT NULL, time TIMESTAMP(0) WITH TIME ZONE NOT NULL, ccq INT DEFAULT NULL, rx_rate INT DEFAULT NULL, tx_rate INT DEFAULT NULL, signal INT DEFAULT NULL, remote_signal INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_99A4B46794A4C7D4 ON wireless_statistics_short_term (device_id)');
        $this->addSql('ALTER TABLE wireless_statistics_long_term ADD CONSTRAINT FK_7A12C76394A4C7D4 FOREIGN KEY (device_id) REFERENCES device (device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wireless_statistics_service_long_term ADD CONSTRAINT FK_9BB5099BCC35FD9E FOREIGN KEY (service_device_id) REFERENCES service_device (service_device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wireless_statistics_service_short_term ADD CONSTRAINT FK_2A236987CC35FD9E FOREIGN KEY (service_device_id) REFERENCES service_device (service_device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wireless_statistics_short_term ADD CONSTRAINT FK_99A4B46794A4C7D4 FOREIGN KEY (device_id) REFERENCES device (device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE general ALTER code TYPE VARCHAR(255)');
        $this->addSql('INSERT INTO general (general_id, code, value) VALUES (nextval(\'general_general_id_seq\'), \'wireless_statistics_aggregation_date\', NULL)');
        $this->addSql('INSERT INTO general (general_id, code, value) VALUES (nextval(\'general_general_id_seq\'), \'wireless_statistics_service_aggregation_date\', NULL)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE wireless_statistics_long_term');
        $this->addSql('DROP TABLE wireless_statistics_service_long_term');
        $this->addSql('DROP TABLE wireless_statistics_service_short_term');
        $this->addSql('DROP TABLE wireless_statistics_short_term');

        $this->addSql('DELETE FROM general WHERE code = \'wireless_statistics_aggregation_date\'');
        $this->addSql('DELETE FROM general WHERE code = \'wireless_statistics_service_aggregation_date\'');
    }
}
