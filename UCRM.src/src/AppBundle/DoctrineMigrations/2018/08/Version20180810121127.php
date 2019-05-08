<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180810121127 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
        $this->addSql('DROP SEQUENCE webhook_address_event_id_seq CASCADE');
        $this->addSql('DROP TABLE webhook_address_event');

        $this->addSql('CREATE TABLE webhook_address_webhook_event_type (webhook_address_id INT NOT NULL, webhook_event_type_id INT NOT NULL, PRIMARY KEY(webhook_address_id, webhook_event_type_id))');
        $this->addSql('CREATE INDEX IDX_A6157D7DAC31D930 ON webhook_address_webhook_event_type (webhook_address_id)');
        $this->addSql('CREATE INDEX IDX_A6157D7D4BF4922B ON webhook_address_webhook_event_type (webhook_event_type_id)');
        $this->addSql('CREATE TABLE webhook_event_type (id SERIAL NOT NULL, event_name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CA936DC75E237E06 ON webhook_event_type (event_name)');
        $this->addSql('ALTER TABLE webhook_address_webhook_event_type ADD CONSTRAINT FK_A6157D7DAC31D930 FOREIGN KEY (webhook_address_id) REFERENCES webhook_address (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE webhook_address_webhook_event_type ADD CONSTRAINT FK_A6157D7D4BF4922B FOREIGN KEY (webhook_event_type_id) REFERENCES webhook_event_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE webhook_event ADD event_name VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE webhook_address ADD any_event BOOLEAN DEFAULT \'true\' NOT NULL');

        $this->addSql("INSERT INTO webhook_event_type (event_name) VALUES 
            ('client.add'),
            ('client.archive'),
            ('client.delete'),
            ('client.edit'),
            ('client.invite'),
            ('invoice.add'),
            ('invoice.add_draft'),
            ('invoice.delete'),
            ('invoice.edit'),
            ('invoice.near_due'),
            ('invoice.overdue'),
            ('payment.add'),
            ('payment.delete'),
            ('payment.edit'),
            ('payment.unmatch'),
            ('quote.add'),
            ('quote.delete'),
            ('quote.edit'),
            ('service.activate'),
            ('service.add'),
            ('service.archive'),
            ('service.edit'),
            ('service.end'),
            ('service.postpone'),
            ('service.suspend'),
            ('service.suspend_cancel'),
            ('subscription.delete'),
            ('subscription.edit'),
            ('ticket.add'),
            ('ticket.comment'),
            ('ticket.delete'),
            ('ticket.edit'),
            ('ticket.status_change'),
            ('user.reset_password')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE webhook_address_webhook_event_type DROP CONSTRAINT FK_A6157D7D4BF4922B');
        $this->addSql('DROP TABLE webhook_address_webhook_event_type');
        $this->addSql('DROP TABLE webhook_event_type');
        $this->addSql('ALTER TABLE webhook_event DROP event_name');
        $this->addSql('ALTER TABLE webhook_address DROP any_event');

        $this->addSql('CREATE SEQUENCE webhook_address_event_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE webhook_address_event (id SERIAL NOT NULL, web_hook_address_id INT DEFAULT NULL, event VARCHAR(255) NOT NULL, is_active BOOLEAN DEFAULT \'true\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_7e4ca90d065b905 ON webhook_address_event (web_hook_address_id)');
        $this->addSql('CREATE INDEX idx_7e4ca903bae0aa7 ON webhook_address_event (event)');
        $this->addSql('ALTER TABLE webhook_address_event ADD CONSTRAINT fk_7e4ca90d065b905 FOREIGN KEY (web_hook_address_id) REFERENCES webhook_address (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
