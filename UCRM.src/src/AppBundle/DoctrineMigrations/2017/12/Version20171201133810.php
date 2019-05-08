<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171201133810 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization ADD quote_number_prefix VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD quote_number_length INT DEFAULT 6 NOT NULL');
        $this->addSql('ALTER TABLE organization ADD quote_init_number INT DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization DROP quote_number_prefix');
        $this->addSql('ALTER TABLE organization DROP quote_number_length');
        $this->addSql('ALTER TABLE organization DROP quote_init_number');
    }
}
