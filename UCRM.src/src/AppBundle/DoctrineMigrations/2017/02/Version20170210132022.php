<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170210132022 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice_item_service ADD original_service_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice_item_service ADD CONSTRAINT FK_550793F280EBDE72 FOREIGN KEY (original_service_id) REFERENCES service (service_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_550793F280EBDE72 ON invoice_item_service (original_service_id)');
        $this->addSql('ALTER TABLE service ADD superseded_by_service_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD2D1B13435 FOREIGN KEY (superseded_by_service_id) REFERENCES service (service_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E19D9AD2D1B13435 ON service (superseded_by_service_id)');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice_item_service DROP CONSTRAINT FK_550793F280EBDE72');
        $this->addSql('DROP INDEX IDX_550793F280EBDE72');
        $this->addSql('ALTER TABLE invoice_item_service DROP original_service_id');
        $this->addSql('ALTER TABLE service DROP CONSTRAINT FK_E19D9AD2D1B13435');
        $this->addSql('DROP INDEX IDX_E19D9AD2D1B13435');
        $this->addSql('ALTER TABLE service DROP superseded_by_service_id');
    }
}
