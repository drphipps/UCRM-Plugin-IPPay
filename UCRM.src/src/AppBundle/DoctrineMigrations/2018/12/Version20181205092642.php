<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181205092642 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client ADD is_address_gps_custom BOOLEAN DEFAULT \'false\' NOT NULL');
        // presume GPS is custom when filled for existing data
        $this->addSql(
            '
              UPDATE client
              SET is_address_gps_custom = true
              WHERE address_gps_lon IS NOT NULL or address_gps_lat IS NOT NULL
            '
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client DROP is_address_gps_custom');
    }
}
