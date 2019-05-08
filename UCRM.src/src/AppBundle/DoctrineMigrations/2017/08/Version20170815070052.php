<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170815070052 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE fee ADD service_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fee ADD CONSTRAINT FK_964964B5ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (service_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_964964B5ED5CA9E6 ON fee (service_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE fee DROP CONSTRAINT FK_964964B5ED5CA9E6');
        $this->addSql('DROP INDEX IDX_964964B5ED5CA9E6');
        $this->addSql('ALTER TABLE fee DROP service_id');
    }
}
