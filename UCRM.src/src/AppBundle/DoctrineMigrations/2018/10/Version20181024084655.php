<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181024084655 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE service SET address_gps_lat = NULL, address_gps_lon = NULL WHERE address_gps_lat < -90 OR address_gps_lat > 90 OR  address_gps_lon < -180 OR address_gps_lon > 180');
        $this->addSql('UPDATE client SET address_gps_lat = NULL, address_gps_lon = NULL WHERE address_gps_lat < -90 OR address_gps_lat > 90 OR  address_gps_lon < -180 OR address_gps_lon > 180');
    }

    public function down(Schema $schema): void
    {
    }
}
