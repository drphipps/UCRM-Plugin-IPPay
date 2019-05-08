<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170215113818 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service DROP CONSTRAINT fk_e19d9ad2f1d00c71');
        $this->addSql('DROP INDEX idx_e19d9ad2f1d00c71');
        $this->addSql('ALTER TABLE service DROP payment_plan_id');
        $this->addSql('ALTER TABLE payment_plan ADD start_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_plan ALTER status SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN payment_plan.start_date IS \'(DC2Type:datetime_utc)\'');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service ADD payment_plan_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT fk_e19d9ad2f1d00c71 FOREIGN KEY (payment_plan_id) REFERENCES payment_plan (payment_plan_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_e19d9ad2f1d00c71 ON service (payment_plan_id)');
        $this->addSql('ALTER TABLE payment_plan DROP start_date');
        $this->addSql('ALTER TABLE payment_plan ALTER status DROP NOT NULL');
    }
}
