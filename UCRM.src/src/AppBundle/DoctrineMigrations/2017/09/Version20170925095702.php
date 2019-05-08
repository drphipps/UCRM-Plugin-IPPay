<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170925095702 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE fee DROP CONSTRAINT FK_964964B519EB6921');
        $this->addSql('ALTER TABLE fee DROP CONSTRAINT FK_964964B5ED5CA9E6');
        $this->addSql('ALTER TABLE fee ADD CONSTRAINT FK_964964B519EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE fee ADD CONSTRAINT FK_964964B5ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (service_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE fee DROP CONSTRAINT fk_964964b519eb6921');
        $this->addSql('ALTER TABLE fee DROP CONSTRAINT fk_964964b5ed5ca9e6');
        $this->addSql('ALTER TABLE fee ADD CONSTRAINT fk_964964b519eb6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE fee ADD CONSTRAINT fk_964964b5ed5ca9e6 FOREIGN KEY (service_id) REFERENCES service (service_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
