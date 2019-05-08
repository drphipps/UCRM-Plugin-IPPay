<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181107120744 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO option (code, value) VALUES (\'GENERATE_PROFORMA_INVOICES\', 0)');
        $this->addSql('ALTER TABLE client ADD generate_proforma_invoices BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD proforma_invoice_number_prefix VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD proforma_invoice_number_length INT DEFAULT 6 NOT NULL');
        $this->addSql('ALTER TABLE organization ADD proforma_invoice_init_number INT DEFAULT 1');
        $this->addSql('ALTER TABLE invoice ADD proforma_invoice_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice ADD generated_invoice_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice ADD is_proforma BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517447717CC92 FOREIGN KEY (proforma_invoice_id) REFERENCES invoice (invoice_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744A43301A2 FOREIGN KEY (generated_invoice_id) REFERENCES invoice (invoice_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_906517447717CC92 ON invoice (proforma_invoice_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_90651744A43301A2 ON invoice (generated_invoice_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE code = \'GENERATE_PROFORMA_INVOICES\'');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT FK_906517447717CC92');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT FK_90651744A43301A2');
        $this->addSql('DROP INDEX UNIQ_906517447717CC92');
        $this->addSql('DROP INDEX UNIQ_90651744A43301A2');
        $this->addSql('ALTER TABLE invoice DROP proforma_invoice_id');
        $this->addSql('ALTER TABLE invoice DROP generated_invoice_id');
        $this->addSql('ALTER TABLE invoice DROP is_proforma');
        $this->addSql('ALTER TABLE client DROP generate_proforma_invoices');
        $this->addSql('ALTER TABLE organization DROP proforma_invoice_number_prefix');
        $this->addSql('ALTER TABLE organization DROP proforma_invoice_number_length');
        $this->addSql('ALTER TABLE organization DROP proforma_invoice_init_number');
    }
}
