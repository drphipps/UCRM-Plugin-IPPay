<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180719094934 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE csv_import_mapping (id SERIAL NOT NULL, hash VARCHAR(40) NOT NULL, mapping JSON NOT NULL, type VARCHAR(20) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_80D34E7BD1B862B8 ON csv_import_mapping (hash)');
        $this->addSql('CREATE INDEX IDX_80D34E7BD1B862B88CDE5729 ON csv_import_mapping (hash, type)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE csv_import_mapping');
    }
}
