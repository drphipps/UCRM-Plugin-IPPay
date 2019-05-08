<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181115161910 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization ADD invoiced_total_rounding_precision INT DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD invoiced_total_rounding_mode INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE organization ADD rounding_total_enabled BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE quote ADD total_rounding_difference DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE quote ADD total_rounding_precision INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quote ADD total_rounding_mode INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE invoice ADD total_rounding_difference DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE invoice ADD total_rounding_precision INT DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice ADD total_rounding_mode INT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization DROP invoiced_total_rounding_precision');
        $this->addSql('ALTER TABLE organization DROP invoiced_total_rounding_mode');
        $this->addSql('ALTER TABLE organization DROP rounding_total_enabled');
        $this->addSql('ALTER TABLE quote DROP total_rounding_difference');
        $this->addSql('ALTER TABLE quote DROP total_rounding_precision');
        $this->addSql('ALTER TABLE quote DROP total_rounding_mode');
        $this->addSql('ALTER TABLE invoice DROP total_rounding_difference');
        $this->addSql('ALTER TABLE invoice DROP total_rounding_precision');
        $this->addSql('ALTER TABLE invoice DROP total_rounding_mode');
    }
}
