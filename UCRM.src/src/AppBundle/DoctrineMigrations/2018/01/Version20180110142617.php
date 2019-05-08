<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180110142617 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql("INSERT INTO option(code, value) VALUES ('FCC_ALWAYS_USE_GPS', 0)");
        $this->addSql('ALTER TABLE service ADD fcc_block_id_by_gps VARCHAR(15) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE code = \'FCC_ALWAYS_USE_GPS\'');
        $this->addSql('ALTER TABLE service DROP fcc_block_id_by_gps');
    }
}
