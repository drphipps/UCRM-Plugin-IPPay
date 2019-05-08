<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170808094659 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device_interface ALTER internal_id TYPE VARCHAR(128)');
        $this->addSql('ALTER TABLE device_interface ALTER internal_name TYPE VARCHAR(128)');
        $this->addSql('ALTER TABLE device_interface ALTER internal_type TYPE VARCHAR(128)');
        $this->addSql('ALTER TABLE device_interface ALTER interface_model TYPE VARCHAR(128)');
        $this->addSql('ALTER TABLE device_interface ALTER band TYPE VARCHAR(128)');
        $this->addSql('ALTER TABLE device_interface ALTER wireless_protocol TYPE VARCHAR(128)');
        $this->addSql('ALTER TABLE payment_stripe ALTER balance_transaction DROP NOT NULL');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device_interface ALTER internal_id TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE device_interface ALTER internal_name TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE device_interface ALTER internal_type TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE device_interface ALTER interface_model TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE device_interface ALTER band TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE device_interface ALTER wireless_protocol TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE payment_stripe ALTER balance_transaction SET NOT NULL');
    }
}
