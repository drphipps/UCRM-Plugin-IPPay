<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160524151850 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (23, 1, \'AppBundle\Controller\InvoicedRevenueController\', \'edit\')');
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (24, 1, \'AppBundle\Controller\TaxReportController\', \'edit\')');
        $this->addSql('CREATE INDEX invoice_created_date_idx ON invoice (created_date)');
        $this->addSql('CREATE INDEX invoice_due_date_idx ON invoice (due_date)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM user_group_permission WHERE permission_id=23');
        $this->addSql('DELETE FROM user_group_permission WHERE permission_id=24');
        $this->addSql('DROP INDEX invoice_created_date_idx');
        $this->addSql('DROP INDEX invoice_due_date_idx');
    }
}
