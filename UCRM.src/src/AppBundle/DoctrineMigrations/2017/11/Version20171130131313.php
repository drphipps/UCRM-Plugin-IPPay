<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171130131313 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE currency SET code = \'BYN\' WHERE currency_id = 92');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE currency SET code = \'BYR\' WHERE currency_id = 92');
    }
}
