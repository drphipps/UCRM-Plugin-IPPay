<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170331082159 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service DROP CONSTRAINT FK_E19D9AD2D1B13435');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD2D1B13435 FOREIGN KEY (superseded_by_service_id) REFERENCES service (service_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service DROP CONSTRAINT fk_e19d9ad2d1b13435');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT fk_e19d9ad2d1b13435 FOREIGN KEY (superseded_by_service_id) REFERENCES service (service_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
