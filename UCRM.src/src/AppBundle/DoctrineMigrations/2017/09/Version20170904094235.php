<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170904094235 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE report_data_usage (id SERIAL NOT NULL, service_id INT NOT NULL, report_created DATE NOT NULL, current_period_start DATE NOT NULL, current_period_end DATE NOT NULL, current_period_download BIGINT NOT NULL, current_period_upload BIGINT NOT NULL, last_period_start DATE DEFAULT NULL, last_period_end DATE DEFAULT NULL, last_period_download BIGINT DEFAULT NULL, last_period_upload BIGINT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8D16BED4ED5CA9E6 ON report_data_usage (service_id)');
        $this->addSql('ALTER TABLE report_data_usage ADD CONSTRAINT FK_8D16BED4ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (service_id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE tariff ADD data_usage_limit DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE report_data_usage');

        $this->addSql('ALTER TABLE tariff DROP data_usage_limit');
    }
}
