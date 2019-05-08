<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170309134404 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE invoice_template (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, error_notification_sent BOOLEAN DEFAULT \'false\' NOT NULL, official_name VARCHAR(100) DEFAULT NULL, is_valid BOOLEAN DEFAULT \'true\' NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN invoice_template.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN invoice_template.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER SEQUENCE invoice_template_id_seq RESTART WITH 1000');

        $this->addSql('INSERT INTO invoice_template (id, name, created_date, official_name) VALUES (1, \'Default\', now(), \'default\')');
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (44, 1, \'AppBundle\Controller\InvoiceTemplateController\', \'edit\')');
        $this->addSql('ALTER TABLE organization ADD invoice_template_id INT NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE organization ALTER COLUMN invoice_template_id DROP DEFAULT');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C884845DB FOREIGN KEY (invoice_template_id) REFERENCES invoice_template (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C1EE637C12946D8B ON organization (invoice_template_id)');

        $this->addSql('COMMENT ON COLUMN invoice_template.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE invoice ADD invoice_template_id INT NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE invoice ALTER COLUMN invoice_template_id DROP DEFAULT');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_9065174412946D8B FOREIGN KEY (invoice_template_id) REFERENCES invoice_template (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_9065174412946D8B ON invoice (invoice_template_id)');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE organization DROP CONSTRAINT FK_C1EE637C884845DB');
        $this->addSql('DROP INDEX IDX_C1EE637C12946D8B');
        $this->addSql('ALTER TABLE organization DROP invoice_template_id');
        $this->addSql('DELETE FROM user_group_permission WHERE permission_id = 44');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT FK_9065174412946D8B');
        $this->addSql('DROP INDEX IDX_9065174412946D8B');
        $this->addSql('ALTER TABLE invoice DROP invoice_template_id');
        $this->addSql('DROP TABLE invoice_template');
    }
}
