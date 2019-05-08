<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180718151253 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client ALTER street1 DROP NOT NULL');
        $this->addSql('ALTER TABLE client ALTER city DROP NOT NULL');
        $this->addSql('ALTER TABLE client ALTER zip_code DROP NOT NULL');
        $this->addSql('ALTER TABLE invoice ALTER client_street1 DROP NOT NULL');
        $this->addSql('ALTER TABLE invoice ALTER client_city DROP NOT NULL');
        $this->addSql('ALTER TABLE invoice ALTER client_zip_code DROP NOT NULL');
        $this->addSql('ALTER TABLE quote ALTER client_street1 DROP NOT NULL');
        $this->addSql('ALTER TABLE quote ALTER client_city DROP NOT NULL');
        $this->addSql('ALTER TABLE quote ALTER client_zip_code DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client ALTER street1 SET NOT NULL');
        $this->addSql('ALTER TABLE client ALTER city SET NOT NULL');
        $this->addSql('ALTER TABLE client ALTER zip_code SET NOT NULL');
        $this->addSql('ALTER TABLE invoice ALTER client_street1 SET NOT NULL');
        $this->addSql('ALTER TABLE invoice ALTER client_city SET NOT NULL');
        $this->addSql('ALTER TABLE invoice ALTER client_zip_code SET NOT NULL');
        $this->addSql('ALTER TABLE quote ALTER client_street1 SET NOT NULL');
        $this->addSql('ALTER TABLE quote ALTER client_city SET NOT NULL');
        $this->addSql('ALTER TABLE quote ALTER client_zip_code SET NOT NULL');
    }
}
