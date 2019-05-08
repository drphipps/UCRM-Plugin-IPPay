<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160909131442 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_device ADD create_ping_statistics BOOLEAN DEFAULT \'true\' NOT NULL');
        $this->addSql('ALTER TABLE service_device ALTER send_ping_notifications SET DEFAULT \'false\'');
        $this->addSql('ALTER TABLE device DROP CONSTRAINT FK_92FB68EB21818BE');
        $this->addSql('ALTER TABLE device ADD CONSTRAINT FK_92FB68EB21818BE FOREIGN KEY (ping_notification_user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_device DROP CONSTRAINT FK_37E8B3B8B21818BE');
        $this->addSql('ALTER TABLE service_device ADD CONSTRAINT FK_37E8B3B8B21818BE FOREIGN KEY (ping_notification_user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_device DROP create_ping_statistics');
        $this->addSql('ALTER TABLE service_device ALTER send_ping_notifications SET DEFAULT \'true\'');
        $this->addSql('ALTER TABLE device DROP CONSTRAINT fk_92fb68eb21818be');
        $this->addSql('ALTER TABLE device ADD CONSTRAINT fk_92fb68eb21818be FOREIGN KEY (ping_notification_user_id) REFERENCES "user" (user_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_device DROP CONSTRAINT fk_37e8b3b8b21818be');
        $this->addSql('ALTER TABLE service_device ADD CONSTRAINT fk_37e8b3b8b21818be FOREIGN KEY (ping_notification_user_id) REFERENCES "user" (user_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
