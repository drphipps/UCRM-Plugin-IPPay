<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180209155416 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE webhook_event (id SERIAL NOT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, uuid VARCHAR(255) NOT NULL, change_type VARCHAR(255) NOT NULL, entity VARCHAR(255) NOT NULL, entity_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B17EEFDEADE9C86E ON webhook_event (created_date)');
        $this->addSql('COMMENT ON COLUMN webhook_event.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('CREATE TABLE webhook_address_event (id SERIAL NOT NULL, web_hook_address_id INT DEFAULT NULL, event VARCHAR(255) NOT NULL, is_active BOOLEAN DEFAULT \'true\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7E4CA90D065B905 ON webhook_address_event (web_hook_address_id)');
        $this->addSql('CREATE INDEX IDX_7E4CA903BAE0AA7 ON webhook_address_event (event)');
        $this->addSql('CREATE TABLE webhook_event_request (id SERIAL NOT NULL, webhook_event_id INT DEFAULT NULL, webhook_address_id INT DEFAULT NULL, request_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, response_code INT DEFAULT NULL, reason_phrase VARCHAR(255) DEFAULT NULL, duration INT DEFAULT NULL, request_body VARCHAR(255) DEFAULT NULL, response_body TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B52F91278F0D6EA3 ON webhook_event_request (webhook_event_id)');
        $this->addSql('CREATE INDEX IDX_B52F9127AC31D930 ON webhook_event_request (webhook_address_id)');
        $this->addSql('CREATE INDEX IDX_B52F9127D5391080 ON webhook_event_request (request_date)');
        $this->addSql('COMMENT ON COLUMN webhook_event_request.request_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('CREATE TABLE webhook_address (id SERIAL NOT NULL, url VARCHAR(255) NOT NULL, is_active BOOLEAN DEFAULT \'true\' NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_23D33119F47645AE ON webhook_address (url)');
        $this->addSql('COMMENT ON COLUMN webhook_address.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE webhook_address_event ADD CONSTRAINT FK_7E4CA90D065B905 FOREIGN KEY (web_hook_address_id) REFERENCES webhook_address (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE webhook_event_request ADD CONSTRAINT FK_B52F91278F0D6EA3 FOREIGN KEY (webhook_event_id) REFERENCES webhook_event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE webhook_event_request ADD CONSTRAINT FK_B52F9127AC31D930 FOREIGN KEY (webhook_address_id) REFERENCES webhook_address (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('INSERT INTO user_group_permission (group_id, module_name, permission) VALUES (1, \'AppBundle\Controller\WebhookEndpointController\', \'edit\')');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM user_group_permission WHERE module_name=\'AppBundle\Controller\WebhookEndpointController\'');
        $this->addSql('ALTER TABLE webhook_event_request DROP CONSTRAINT FK_B52F91278F0D6EA3');
        $this->addSql('ALTER TABLE webhook_address_event DROP CONSTRAINT FK_7E4CA90D065B905');
        $this->addSql('ALTER TABLE webhook_event_request DROP CONSTRAINT FK_B52F9127AC31D930');
        $this->addSql('DROP TABLE webhook_event');
        $this->addSql('DROP TABLE webhook_address_event');
        $this->addSql('DROP TABLE webhook_event_request');
        $this->addSql('DROP TABLE webhook_address');
    }
}
