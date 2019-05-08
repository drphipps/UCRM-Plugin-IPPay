<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160816093830 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_device ADD service_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_device ADD vendor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_device ALTER mac_address DROP NOT NULL');
        $this->addSql('ALTER TABLE service_device ALTER first_seen DROP NOT NULL');
        $this->addSql('ALTER TABLE service_device ALTER last_seen DROP NOT NULL');
        $this->addSql('ALTER TABLE service_device ADD login_username VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE service_device ADD login_password TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_device ADD ssh_port INT DEFAULT 22');

        $this->addSql('ALTER TABLE service_device ADD CONSTRAINT FK_37E8B3B8ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (service_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_37E8B3B8ED5CA9E6 ON service_device (service_id)');
        $this->addSql('
            DO
            $do$
            DECLARE
                    _rec record;
                    _service record;
            BEGIN
                    FOR _rec IN SELECT DISTINCT service_id FROM public.service_ip LOOP
                            SELECT * INTO _service
                            FROM public.service;

                            INSERT INTO public.service_device
                                    (service_device_id, service_id, interface_id)
                                    VALUES
                                    ((SELECT nextval(\'public.service_device_service_device_id_seq\'::regclass)),
                                    _rec.service_id,
                                    _service.interface_id);
                    END LOOP;

            END
            $do$;
        ');

        $this->addSql('ALTER TABLE service_ip DROP CONSTRAINT fk_f7867d9bed5ca9e6');
        $this->addSql('DROP INDEX idx_f7867d9bed5ca9e6');
        $this->addSql('ALTER TABLE service_ip ADD service_device_id INT DEFAULT NULL');
        $this->addSql('
            DO
            $do$
            DECLARE
                    _rec record;
            BEGIN
                    FOR _rec IN SELECT * FROM public.service_device LOOP
                            UPDATE public.service_ip
                            SET service_device_id = _rec.service_device_id
                            WHERE service_id = _rec.service_id;
                    END LOOP;

            END
            $do$;
        ');

        $this->addSql('ALTER TABLE service_ip DROP service_id');
        $this->addSql('ALTER TABLE service_ip ADD CONSTRAINT FK_F7867D9BCC35FD9E FOREIGN KEY (service_device_id) REFERENCES service_device (service_device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_F7867D9BCC35FD9E ON service_ip (service_device_id)');

        $this->addSql('CREATE INDEX IDX_37E8B3B8F603EE73 ON service_device (vendor_id)');
        $this->addSql('ALTER TABLE service_ip DROP mac_address');
        $this->addSql('ALTER TABLE service_ip ALTER service_device_id DROP NOT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_device DROP CONSTRAINT FK_37E8B3B8ED5CA9E6');
        $this->addSql('DROP INDEX IDX_37E8B3B8ED5CA9E6');
        $this->addSql('ALTER TABLE service_ip ADD service_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_ip ADD mac_address VARCHAR(20) DEFAULT NULL');
        $this->addSql('
            DO
            $do$
            DECLARE
                    _rec record;
                    _service record;
            BEGIN
                    FOR _rec IN SELECT service_device_id, service_id, mac_address FROM public.service_device LOOP
                            UPDATE public.service_ip
                            SET service_id = _rec.service_id, mac_address = _rec.mac_address
                            WHERE service_device_id = _rec.service_device_id;
                    END LOOP;

            END
            $do$;
        ');
        $this->addSql('ALTER TABLE service_device DROP service_id');
        $this->addSql('ALTER TABLE service_ip DROP CONSTRAINT FK_F7867D9BCC35FD9E');
        $this->addSql('DROP INDEX IDX_F7867D9BCC35FD9E');
        $this->addSql('ALTER TABLE service_ip DROP service_device_id');
        $this->addSql('ALTER TABLE service_ip ADD CONSTRAINT fk_f7867d9bed5ca9e6 FOREIGN KEY (service_id) REFERENCES service (service_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_f7867d9bed5ca9e6 ON service_ip (service_id)');
        $this->addSql('DROP INDEX IDX_37E8B3B8F603EE73');
        $this->addSql('ALTER TABLE service_device DROP vendor_id');
        $this->addSql('ALTER TABLE service_device DROP login_username');
        $this->addSql('ALTER TABLE service_device DROP login_password');
        $this->addSql('ALTER TABLE service_device DROP ssh_port');
    }
}
