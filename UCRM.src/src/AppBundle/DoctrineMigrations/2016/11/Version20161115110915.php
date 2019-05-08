<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20161115110915 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE service_device_log DROP CONSTRAINT FK_F3908BE8CC35FD9E');
        $this->addSql('ALTER TABLE service_device_log ADD CONSTRAINT FK_F3908BE8CC35FD9E FOREIGN KEY (service_device_id) REFERENCES service_device (service_device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE device_log DROP CONSTRAINT FK_65C1B25C94A4C7D4');
        $this->addSql('ALTER TABLE device_log ADD CONSTRAINT FK_65C1B25C94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE device_log DROP CONSTRAINT fk_65c1b25c94a4c7d4');
        $this->addSql('ALTER TABLE device_log ADD CONSTRAINT fk_65c1b25c94a4c7d4 FOREIGN KEY (device_id) REFERENCES device (device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_device_log DROP CONSTRAINT fk_f3908be8cc35fd9e');
        $this->addSql('ALTER TABLE service_device_log ADD CONSTRAINT fk_f3908be8cc35fd9e FOREIGN KEY (service_device_id) REFERENCES service_device (service_device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
