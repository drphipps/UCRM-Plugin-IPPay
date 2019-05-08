<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171019101409 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE service_ip_accounting_accounting_id_seq CASCADE');
        $this->addSql('CREATE TABLE service_accounting (accounting_id SERIAL NOT NULL, service_id INT NOT NULL, upload BIGINT NOT NULL, download BIGINT NOT NULL, date DATE NOT NULL, PRIMARY KEY(accounting_id))');
        $this->addSql('CREATE INDEX IDX_FB943851ED5CA9E6 ON service_accounting (service_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FB943851ED5CA9E6AA9E377A ON service_accounting (service_id, date)');
        $this->addSql('ALTER TABLE service_accounting ADD CONSTRAINT FK_FB943851ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (service_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql(
            '
            INSERT INTO service_accounting (service_id, upload, download, date)
            SELECT d.service_id, SUM(a.upload), SUM(a.download), a.date
            FROM service_ip_accounting a
                JOIN service_ip i ON i.ip_id = a.service_ip_id
                JOIN service_device d ON d.service_device_id = i.service_device_id
            GROUP BY d.service_id, a.date
            '
        );

        $this->addSql('CREATE TABLE service_accounting_raw (accounting_id SERIAL NOT NULL, service_id INT NOT NULL, upload BIGINT NOT NULL, download BIGINT NOT NULL, time timestamptz NOT NULL, PRIMARY KEY(accounting_id))');

        $this->addSql(
            '
            INSERT INTO service_accounting_raw (service_id, upload, download, time)
            SELECT d.service_id, SUM(a.upload), SUM(a.download), a.time
            FROM service_ip_accounting_raw a
                JOIN service_ip i ON i.ip_id = a.service_ip_id
                JOIN service_device d ON d.service_device_id = i.service_device_id
            GROUP BY d.service_id, a.time
            '
        );

        $this->addSql('DROP TABLE service_ip_accounting');
        $this->addSql('DROP TABLE service_ip_accounting_raw');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'Not implemented.');
    }
}
