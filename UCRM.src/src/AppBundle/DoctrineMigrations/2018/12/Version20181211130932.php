<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181211130932 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service ADD is_address_gps_custom BOOLEAN DEFAULT \'false\' NOT NULL');
        // presume GPS is custom when filled for existing data
        $this->addSql(
            '
              UPDATE service
              SET is_address_gps_custom = true
              WHERE address_gps_lon IS NOT NULL or address_gps_lat IS NOT NULL
            '
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service DROP is_address_gps_custom');
    }
}
