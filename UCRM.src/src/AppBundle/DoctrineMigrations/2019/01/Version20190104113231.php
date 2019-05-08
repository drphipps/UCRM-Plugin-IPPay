<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190104113231 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO tariff_period (tariff_id, price, period) SELECT tariff_id, NULL, 2 FROM tariff');
    }

    public function down(Schema $schema): void
    {
    }
}
