<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170817111111 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO currency (name, code, symbol) VALUES (\'Algerian Dinar\', \'DZD\', \'دج\')');
        $this->addSql('INSERT INTO currency (name, code, symbol) VALUES (\'Moroccan Dirham\', \'MAD\', \'دم\')');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM currency WHERE name IN (\'Algerian Dinar\', \'Moroccan Dirham\')');
    }
}
