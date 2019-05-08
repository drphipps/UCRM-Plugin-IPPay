<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160908075807 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE service_ip_accounting (accounting_id SERIAL NOT NULL, service_ip_id INT NOT NULL, upload BIGINT NOT NULL, download BIGINT NOT NULL, date DATE NOT NULL, PRIMARY KEY(accounting_id))');
        $this->addSql('CREATE INDEX IDX_17EEED3394DB378 ON service_ip_accounting (service_ip_id)');
        $this->addSql('ALTER TABLE service_ip_accounting ADD CONSTRAINT FK_17EEED3394DB378 FOREIGN KEY (service_ip_id) REFERENCES service_ip (ip_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE service_ip_accounting_raw (accounting_id SERIAL NOT NULL, service_ip_id INT NOT NULL, upload BIGINT NOT NULL, download BIGINT NOT NULL, time timestamptz NOT NULL, PRIMARY KEY(accounting_id))');
        $this->addSql('CREATE TABLE ip_accounting (accounting_id SERIAL NOT NULL, ip BIGINT NOT NULL, upload BIGINT NOT NULL, download BIGINT NOT NULL, date DATE NOT NULL, PRIMARY KEY(accounting_id))');
        $this->addSql('CREATE TABLE ip_accounting_raw (accounting_id SERIAL NOT NULL, ip BIGINT NOT NULL, upload BIGINT NOT NULL, download BIGINT NOT NULL, time timestamptz NOT NULL, PRIMARY KEY(accounting_id))');
        $this->addSql('INSERT INTO general (general_id, code, value) VALUES (nextval(\'general_general_id_seq\'), \'netflow_network_aggregation_date\', TO_CHAR((NOW() - INTERVAL \'1 day\'), \'YYYY-MM-DD\'))');
        $this->addSql('INSERT INTO general (general_id, code, value) VALUES (nextval(\'general_general_id_seq\'), \'netflow_service_aggregation_date\', TO_CHAR((NOW() - INTERVAL \'1 day\'), \'YYYY-MM-DD\'))');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE service_ip_accounting_accounting_id_seq CASCADE');
        $this->addSql('DROP TABLE service_ip_accounting');
        $this->addSql('DROP SEQUENCE service_ip_accounting_raw_accounting_id_seq CASCADE');
        $this->addSql('DROP TABLE service_ip_accounting_raw');
        $this->addSql('DROP SEQUENCE ip_accounting_accounting_id_seq CASCADE');
        $this->addSql('DROP TABLE ip_accounting');
        $this->addSql('DROP SEQUENCE ip_accounting_raw_accounting_id_seq CASCADE');
        $this->addSql('DROP TABLE ip_accounting_raw');
        $this->addSql('DELETE FROM general WHERE code = \'netflow_network_aggregation_date\'');
        $this->addSql('DELETE FROM general WHERE code = \'netflow_service_aggregation_date\'');
    }
}
