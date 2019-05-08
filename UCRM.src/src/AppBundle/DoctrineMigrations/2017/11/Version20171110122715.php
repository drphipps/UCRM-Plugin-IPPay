<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171110122715 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE service_accounting_correction (id SERIAL NOT NULL, service_id INT NOT NULL, upload BIGINT NOT NULL, download BIGINT NOT NULL, date DATE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_14DD86ADED5CA9E6 ON service_accounting_correction (service_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_14DD86ADED5CA9E6AA9E377A ON service_accounting_correction (service_id, date)');
        $this->addSql('ALTER TABLE service_accounting_correction ADD CONSTRAINT FK_14DD86ADED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (service_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE service_accounting_correction');
    }
}
