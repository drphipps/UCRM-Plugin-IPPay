<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160902083035 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_device ADD CONSTRAINT FK_37E8B3B8F603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (vendor_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_5c89fd3dab0be982 RENAME TO IDX_37E8B3B8AB0BE982');
        $this->addSql('ALTER TABLE service_ip ALTER service_device_id SET NOT NULL');
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (35, 1, \'AppBundle\Controller\OutageController\', \'edit\')');
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (36, 1, \'AppBundle\Controller\UnknownDevicesController\', \'edit\')');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_ip ALTER service_device_id DROP NOT NULL');
        $this->addSql('ALTER TABLE service_device DROP CONSTRAINT FK_37E8B3B8F603EE73');
        $this->addSql('ALTER INDEX idx_37e8b3b8ab0be982 RENAME TO idx_5c89fd3dab0be982');
        $this->addSql('DELETE FROM user_group_permission WHERE permission_id=35');
        $this->addSql('DELETE FROM user_group_permission WHERE permission_id=36');
    }
}
