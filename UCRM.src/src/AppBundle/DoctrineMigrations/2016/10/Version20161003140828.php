<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161003140828 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE entity_log DROP CONSTRAINT FK_F1B00862A76ED395');
        $this->addSql('ALTER TABLE entity_log ADD CONSTRAINT FK_F1B00862A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ping_short_term DROP CONSTRAINT FK_96DF92C794A4C7D4');
        $this->addSql('ALTER TABLE ping_short_term ADD CONSTRAINT FK_96DF92C794A4C7D4 FOREIGN KEY (device_id) REFERENCES device (device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wireless_statistics_service_short_term DROP CONSTRAINT FK_2A236987CC35FD9E');
        $this->addSql('ALTER TABLE wireless_statistics_service_short_term ADD CONSTRAINT FK_2A236987CC35FD9E FOREIGN KEY (service_device_id) REFERENCES service_device (service_device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wireless_statistics_long_term DROP CONSTRAINT FK_7A12C76394A4C7D4');
        $this->addSql('ALTER TABLE wireless_statistics_long_term ADD CONSTRAINT FK_7A12C76394A4C7D4 FOREIGN KEY (device_id) REFERENCES device (device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wireless_statistics_short_term DROP CONSTRAINT FK_99A4B46794A4C7D4');
        $this->addSql('ALTER TABLE wireless_statistics_short_term ADD CONSTRAINT FK_99A4B46794A4C7D4 FOREIGN KEY (device_id) REFERENCES device (device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_ip_accounting DROP CONSTRAINT FK_17EEED3394DB378');
        $this->addSql('ALTER TABLE service_ip_accounting ADD CONSTRAINT FK_17EEED3394DB378 FOREIGN KEY (service_ip_id) REFERENCES service_ip (ip_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ping_long_term DROP CONSTRAINT FK_1CD532A94A4C7D4');
        $this->addSql('ALTER TABLE ping_long_term ADD CONSTRAINT FK_1CD532A94A4C7D4 FOREIGN KEY (device_id) REFERENCES device (device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ping_service_long_term DROP CONSTRAINT FK_DB8AFD5694A4C7D4');
        $this->addSql('ALTER TABLE ping_service_long_term ADD CONSTRAINT FK_DB8AFD5694A4C7D4 FOREIGN KEY (device_id) REFERENCES service_device (service_device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ping_service_short_term DROP CONSTRAINT FK_CFB6E87E94A4C7D4');
        $this->addSql('ALTER TABLE ping_service_short_term ADD CONSTRAINT FK_CFB6E87E94A4C7D4 FOREIGN KEY (device_id) REFERENCES service_device (service_device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_device_outage DROP CONSTRAINT FK_C0D14661CC35FD9E');
        $this->addSql('ALTER TABLE service_device_outage ADD CONSTRAINT FK_C0D14661CC35FD9E FOREIGN KEY (service_device_id) REFERENCES service_device (service_device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wireless_statistics_service_long_term DROP CONSTRAINT FK_9BB5099BCC35FD9E');
        $this->addSql('ALTER TABLE wireless_statistics_service_long_term ADD CONSTRAINT FK_9BB5099BCC35FD9E FOREIGN KEY (service_device_id) REFERENCES service_device (service_device_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE entity_log DROP CONSTRAINT fk_f1b00862a76ed395');
        $this->addSql('ALTER TABLE entity_log ADD CONSTRAINT fk_f1b00862a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_device_outage DROP CONSTRAINT fk_c0d14661cc35fd9e');
        $this->addSql('ALTER TABLE service_device_outage ADD CONSTRAINT fk_c0d14661cc35fd9e FOREIGN KEY (service_device_id) REFERENCES service_device (service_device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_ip_accounting DROP CONSTRAINT fk_17eeed3394db378');
        $this->addSql('ALTER TABLE service_ip_accounting ADD CONSTRAINT fk_17eeed3394db378 FOREIGN KEY (service_ip_id) REFERENCES service_ip (ip_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ping_long_term DROP CONSTRAINT fk_1cd532a94a4c7d4');
        $this->addSql('ALTER TABLE ping_long_term ADD CONSTRAINT fk_1cd532a94a4c7d4 FOREIGN KEY (device_id) REFERENCES device (device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ping_service_long_term DROP CONSTRAINT fk_db8afd5694a4c7d4');
        $this->addSql('ALTER TABLE ping_service_long_term ADD CONSTRAINT fk_db8afd5694a4c7d4 FOREIGN KEY (device_id) REFERENCES service_device (service_device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ping_service_short_term DROP CONSTRAINT fk_cfb6e87e94a4c7d4');
        $this->addSql('ALTER TABLE ping_service_short_term ADD CONSTRAINT fk_cfb6e87e94a4c7d4 FOREIGN KEY (device_id) REFERENCES service_device (service_device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ping_short_term DROP CONSTRAINT fk_96df92c794a4c7d4');
        $this->addSql('ALTER TABLE ping_short_term ADD CONSTRAINT fk_96df92c794a4c7d4 FOREIGN KEY (device_id) REFERENCES device (device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wireless_statistics_long_term DROP CONSTRAINT fk_7a12c76394a4c7d4');
        $this->addSql('ALTER TABLE wireless_statistics_long_term ADD CONSTRAINT fk_7a12c76394a4c7d4 FOREIGN KEY (device_id) REFERENCES device (device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wireless_statistics_service_long_term DROP CONSTRAINT fk_9bb5099bcc35fd9e');
        $this->addSql('ALTER TABLE wireless_statistics_service_long_term ADD CONSTRAINT fk_9bb5099bcc35fd9e FOREIGN KEY (service_device_id) REFERENCES service_device (service_device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wireless_statistics_service_short_term DROP CONSTRAINT fk_2a236987cc35fd9e');
        $this->addSql('ALTER TABLE wireless_statistics_service_short_term ADD CONSTRAINT fk_2a236987cc35fd9e FOREIGN KEY (service_device_id) REFERENCES service_device (service_device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wireless_statistics_short_term DROP CONSTRAINT fk_99a4b46794a4c7d4');
        $this->addSql('ALTER TABLE wireless_statistics_short_term ADD CONSTRAINT fk_99a4b46794a4c7d4 FOREIGN KEY (device_id) REFERENCES device (device_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
