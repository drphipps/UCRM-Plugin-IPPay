<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160527074658 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice ADD pdf_batch_printed BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE client ADD send_invoice_by_post BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE client ALTER send_invoice_by_email SET DEFAULT \'true\'');
        $this->addSql('INSERT INTO option (option_id, position, code, name, description, type, value, category_id, value_txt, choice_type_options) VALUES (27, 3, \'SEND_INVOICE_BY_POST\', \'Send invoice by post\', \'If checked, the approved invoices are marked as to be sent by post. You can then batch print only these.\', \'bool\', 0, \'2\', NULL, NULL)');
        $this->addSql('UPDATE option SET position = 4 WHERE option_id = 7');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice DROP pdf_batch_printed');
        $this->addSql('ALTER TABLE client DROP send_invoice_by_post');
        $this->addSql('ALTER TABLE client ALTER send_invoice_by_email DROP DEFAULT');
        $this->addSql('DELETE FROM option WHERE option_id = 27');
        $this->addSql('UPDATE option SET position = 3 WHERE option_id = 7');
    }
}
