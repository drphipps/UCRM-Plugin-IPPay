<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181109144655 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            sprintf(
                'INSERT INTO notification_template (template_id, subject, body, type) VALUES (30, \'New proforma invoice\', \'%s\', 30);',
                '<p>Dear %CLIENT_NAME%! We are sending you new proforma invoice for internet services.</p>'
            )
        );
        $this->addSql('CREATE TABLE proforma_invoice_template (id SERIAL NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, name VARCHAR(255) NOT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, error_notification_sent BOOLEAN DEFAULT \'false\' NOT NULL, official_name VARCHAR(100) DEFAULT NULL, is_valid BOOLEAN DEFAULT \'true\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_13E6114E5E237E06 ON proforma_invoice_template (name)');
        $this->addSql('COMMENT ON COLUMN proforma_invoice_template.deleted_at IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN proforma_invoice_template.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('INSERT INTO proforma_invoice_template (id, name, created_date, official_name) VALUES (1, \'Default\', now(), \'default\')');
        $this->addSql('ALTER SEQUENCE proforma_invoice_template_id_seq RESTART WITH 1000');
        $this->addSql('ALTER TABLE organization ADD proforma_invoice_template_id INT DEFAULT NULL');
        $this->addSql('UPDATE organization SET proforma_invoice_template_id = 1');
        $this->addSql('ALTER TABLE organization ALTER proforma_invoice_template_id SET NOT NULL');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C6F501B4 FOREIGN KEY (proforma_invoice_template_id) REFERENCES proforma_invoice_template (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C1EE637C6F501B4 ON organization (proforma_invoice_template_id)');
        $this->addSql('ALTER TABLE invoice ADD proforma_invoice_template_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice ALTER invoice_template_id DROP NOT NULL');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517446F501B4 FOREIGN KEY (proforma_invoice_template_id) REFERENCES proforma_invoice_template (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_906517446F501B4 ON invoice (proforma_invoice_template_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM notification_template WHERE template_id = 30');
        $this->addSql('ALTER TABLE organization DROP CONSTRAINT FK_C1EE637C6F501B4');
        $this->addSql('DROP TABLE proforma_invoice_template');
        $this->addSql('DROP INDEX IDX_C1EE637C6F501B4');
        $this->addSql('ALTER TABLE organization DROP proforma_invoice_template_id');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT FK_906517446F501B4');
        $this->addSql('DROP INDEX IDX_906517446F501B4');
        $this->addSql('ALTER TABLE invoice DROP proforma_invoice_template_id');
        $this->addSql('ALTER TABLE invoice ALTER invoice_template_id SET NOT NULL');
    }
}
