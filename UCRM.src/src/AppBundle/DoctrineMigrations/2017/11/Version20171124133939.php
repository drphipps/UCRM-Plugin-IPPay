<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171124133939 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER INDEX idx_14dd86aded5ca9e6 RENAME TO IDX_36BA8AC6ED5CA9E6');
        $this->addSql('ALTER INDEX uniq_14dd86aded5ca9e6aa9e377a RENAME TO UNIQ_36BA8AC6ED5CA9E6AA9E377A');
        $this->addSql('ALTER TABLE quote ADD comment TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE quote ADD notes TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE quote DROP amount_paid');
        $this->addSql('ALTER TABLE quote DROP invoice_maturity_days');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER INDEX uniq_36ba8ac6ed5ca9e6aa9e377a RENAME TO uniq_14dd86aded5ca9e6aa9e377a');
        $this->addSql('ALTER INDEX idx_36ba8ac6ed5ca9e6 RENAME TO idx_14dd86aded5ca9e6');
        $this->addSql('ALTER TABLE quote ADD amount_paid DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE quote ADD invoice_maturity_days INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quote DROP comment');
        $this->addSql('ALTER TABLE quote DROP notes');
    }
}
