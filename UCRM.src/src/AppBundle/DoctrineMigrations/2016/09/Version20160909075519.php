<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160909075519 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE ping_raw (ping_id SERIAL NOT NULL, device_id INT NOT NULL, ping DOUBLE PRECISION NOT NULL, packet_loss DOUBLE PRECISION NOT NULL, time timestamptz NOT NULL)');
        $this->addSql('CREATE TABLE ping_service_raw (ping_id SERIAL NOT NULL, device_id INT NOT NULL, ping DOUBLE PRECISION NOT NULL, packet_loss DOUBLE PRECISION NOT NULL, time timestamptz NOT NULL)');

        $this->addSql('CREATE SEQUENCE ping_long_term_ping_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE ping_service_long_term_ping_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE ping_service_short_term_ping_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE ping_short_term_ping_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE ping_long_term (ping_id INT NOT NULL, device_id INT NOT NULL, time DATE NOT NULL, ping DOUBLE PRECISION NOT NULL, packet_loss DOUBLE PRECISION NOT NULL, PRIMARY KEY(ping_id))');
        $this->addSql('CREATE INDEX IDX_1CD532A94A4C7D4 ON ping_long_term (device_id)');
        $this->addSql('CREATE TABLE ping_service_long_term (ping_id INT NOT NULL, device_id INT NOT NULL, time DATE NOT NULL, ping DOUBLE PRECISION NOT NULL, packet_loss DOUBLE PRECISION NOT NULL, PRIMARY KEY(ping_id))');
        $this->addSql('CREATE INDEX IDX_DB8AFD5694A4C7D4 ON ping_service_long_term (device_id)');
        $this->addSql('CREATE TABLE ping_service_short_term (ping_id INT NOT NULL, device_id INT NOT NULL, time TIMESTAMP(0) WITH TIME ZONE NOT NULL, ping DOUBLE PRECISION NOT NULL, packet_loss DOUBLE PRECISION NOT NULL, PRIMARY KEY(ping_id))');
        $this->addSql('CREATE INDEX IDX_CFB6E87E94A4C7D4 ON ping_service_short_term (device_id)');
        $this->addSql('CREATE TABLE ping_short_term (ping_id INT NOT NULL, device_id INT NOT NULL, time TIMESTAMP(0) WITH TIME ZONE NOT NULL, ping DOUBLE PRECISION NOT NULL, packet_loss DOUBLE PRECISION NOT NULL, PRIMARY KEY(ping_id))');
        $this->addSql('CREATE INDEX IDX_96DF92C794A4C7D4 ON ping_short_term (device_id)');
        $this->addSql('ALTER TABLE ping_long_term ADD CONSTRAINT FK_1CD532A94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ping_service_long_term ADD CONSTRAINT FK_DB8AFD5694A4C7D4 FOREIGN KEY (device_id) REFERENCES service_device (service_device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ping_service_short_term ADD CONSTRAINT FK_CFB6E87E94A4C7D4 FOREIGN KEY (device_id) REFERENCES service_device (service_device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ping_short_term ADD CONSTRAINT FK_96DF92C794A4C7D4 FOREIGN KEY (device_id) REFERENCES device (device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('INSERT INTO general (general_id, code, value) VALUES (nextval(\'general_general_id_seq\'), \'ping_network_short_term_aggregation_date\', NULL)');
        $this->addSql('INSERT INTO general (general_id, code, value) VALUES (nextval(\'general_general_id_seq\'), \'ping_network_long_term_aggregation_date\', NULL)');
        $this->addSql('INSERT INTO general (general_id, code, value) VALUES (nextval(\'general_general_id_seq\'), \'ping_service_short_term_aggregation_date\', NULL)');
        $this->addSql('INSERT INTO general (general_id, code, value) VALUES (nextval(\'general_general_id_seq\'), \'ping_service_long_term_aggregation_date\', NULL)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE ping_raw_ping_id_seq CASCADE');
        $this->addSql('DROP TABLE ping_raw');
        $this->addSql('DROP SEQUENCE ping_service_raw_ping_id_seq CASCADE');
        $this->addSql('DROP TABLE ping_service_raw');

        $this->addSql('DROP SEQUENCE ping_long_term_ping_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE ping_service_long_term_ping_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE ping_service_short_term_ping_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE ping_short_term_ping_id_seq CASCADE');
        $this->addSql('DROP TABLE ping_long_term');
        $this->addSql('DROP TABLE ping_service_long_term');
        $this->addSql('DROP TABLE ping_service_short_term');
        $this->addSql('DROP TABLE ping_short_term');

        $this->addSql('DELETE FROM general WHERE code IN (\'ping_network_short_term_aggregation_date\', \'ping_network_long_term_aggregation_date\', \'ping_service_short_term_aggregation_date\', \'ping_service_long_term_aggregation_date\')');
    }
}
