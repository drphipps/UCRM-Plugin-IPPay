<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170109083719 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device_interface_ip DROP CONSTRAINT FK_C3A4DA2EAB0BE982');
        $this->addSql('ALTER TABLE device_interface_ip ADD CONSTRAINT FK_C3A4DA2EAB0BE982 FOREIGN KEY (interface_id) REFERENCES device_interface (interface_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_ip DROP CONSTRAINT FK_F7867D9BCC35FD9E');
        $this->addSql('ALTER TABLE service_ip ADD CONSTRAINT FK_F7867D9BCC35FD9E FOREIGN KEY (service_device_id) REFERENCES service_device (service_device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device_interface_ip DROP CONSTRAINT fk_c3a4da2eab0be982');
        $this->addSql('ALTER TABLE device_interface_ip ADD CONSTRAINT fk_c3a4da2eab0be982 FOREIGN KEY (interface_id) REFERENCES device_interface (interface_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_ip DROP CONSTRAINT fk_f7867d9bcc35fd9e');
        $this->addSql('ALTER TABLE service_ip ADD CONSTRAINT fk_f7867d9bcc35fd9e FOREIGN KEY (service_device_id) REFERENCES service_device (service_device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
