<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180316104352 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tariff ADD fcc_service_type INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tariff ADD maximum_contractual_downstream_bandwidth DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE tariff ADD maximum_contractual_upstream_bandwidth DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tariff DROP fcc_service_type');
        $this->addSql('ALTER TABLE tariff DROP maximum_contractual_downstream_bandwidth');
        $this->addSql('ALTER TABLE tariff DROP maximum_contractual_upstream_bandwidth');
    }
}
