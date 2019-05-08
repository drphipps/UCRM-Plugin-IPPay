<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160913120216 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device_interface_ip ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE device_interface_ip ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN device_interface_ip.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE surcharge ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE surcharge ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN surcharge.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE organization ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE organization ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN organization.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE tax ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE tax ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN tax.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE service ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE service ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN service.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE device ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE device ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN device.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE tariff ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE tariff ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN tariff.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE site ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE site ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN site.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE product ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE product ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN product.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE device_interface ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE device_interface ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN device_interface.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE client ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE client ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN client.deleted_at IS \'(DC2Type:datetime_utc)\'');

        $this->addSql('DELETE FROM device_interface_ip WHERE deleted_at IS NOT NULL');
        $this->addSql('ALTER TABLE device_interface_ip DROP deleted_at');

        // @todo hotfixed, needs proper delete fix, failed on:
        // Foreign key violation: 7 ERROR: update or delete on table "organization" violates foreign key constraint "fk_9465207d32c8a3de" on table "tariff"
        // DETAIL: Key (organization_id)=(1) is still referenced from table "tariff".
        // $this->addSql('DELETE FROM organization WHERE deleted_at IS NOT NULL');
        $this->addSql('ALTER TABLE organization DROP deleted_at');

        $this->addSql('UPDATE tax SET selected = FALSE WHERE deleted_at IS NOT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN organization.deleted_at IS \'(DC2Type:datetime_utc)\'');

        $this->addSql('ALTER TABLE device_interface_ip ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN device_interface_ip.deleted_at IS \'(DC2Type:datetime_utc)\'');

        $this->addSql('ALTER TABLE site ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE site ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN site.deleted_at IS NULL');
        $this->addSql('ALTER TABLE device ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE device ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN device.deleted_at IS NULL');
        $this->addSql('ALTER TABLE device_interface_ip ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE device_interface_ip ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN device_interface_ip.deleted_at IS NULL');
        $this->addSql('ALTER TABLE product ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE product ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN product.deleted_at IS NULL');
        $this->addSql('ALTER TABLE client ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE client ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN client.deleted_at IS NULL');
        $this->addSql('ALTER TABLE device_interface ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE device_interface ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN device_interface.deleted_at IS NULL');
        $this->addSql('ALTER TABLE service ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE service ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN service.deleted_at IS NULL');
        $this->addSql('ALTER TABLE tax ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE tax ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN tax.deleted_at IS NULL');
        $this->addSql('ALTER TABLE tariff ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE tariff ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN tariff.deleted_at IS NULL');
        $this->addSql('ALTER TABLE surcharge ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE surcharge ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN surcharge.deleted_at IS NULL');
        $this->addSql('ALTER TABLE organization ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE organization ALTER deleted_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN organization.deleted_at IS NULL');
    }
}
