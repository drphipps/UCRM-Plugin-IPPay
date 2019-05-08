<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180105102247 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice_item_fee DROP CONSTRAINT FK_6E7445DFAB45AECA');
        $this->addSql('ALTER TABLE invoice_item_fee ADD CONSTRAINT FK_6E7445DFAB45AECA FOREIGN KEY (fee_id) REFERENCES fee (fee_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_item_fee DROP CONSTRAINT FK_9B66B716AB45AECA');
        $this->addSql('ALTER TABLE quote_item_fee ADD CONSTRAINT FK_9B66B716AB45AECA FOREIGN KEY (fee_id) REFERENCES fee (fee_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report_data_usage DROP CONSTRAINT FK_8D16BED4ED5CA9E6');
        $this->addSql('ALTER TABLE report_data_usage ADD CONSTRAINT FK_8D16BED4ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (service_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice_item_fee DROP CONSTRAINT fk_6e7445dfab45aeca');
        $this->addSql('ALTER TABLE invoice_item_fee ADD CONSTRAINT fk_6e7445dfab45aeca FOREIGN KEY (fee_id) REFERENCES fee (fee_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_item_fee DROP CONSTRAINT fk_9b66b716ab45aeca');
        $this->addSql('ALTER TABLE quote_item_fee ADD CONSTRAINT fk_9b66b716ab45aeca FOREIGN KEY (fee_id) REFERENCES fee (fee_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report_data_usage DROP CONSTRAINT fk_8d16bed4ed5ca9e6');
        $this->addSql('ALTER TABLE report_data_usage ADD CONSTRAINT fk_8d16bed4ed5ca9e6 FOREIGN KEY (service_id) REFERENCES service (service_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
