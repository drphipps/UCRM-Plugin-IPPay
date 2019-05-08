<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20161114084116 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE payment_token (token_id SERIAL NOT NULL, invoice_id INT NOT NULL, token VARCHAR(32) NOT NULL, amount DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(token_id))');
        $this->addSql('CREATE INDEX IDX_87E97892989F1FD ON payment_token (invoice_id)');
        $this->addSql('ALTER TABLE payment_token ADD CONSTRAINT FK_87E97892989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (invoice_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invoice DROP payment_token');
        $this->addSql('ALTER TABLE payment_token ADD created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('COMMENT ON COLUMN payment_token.created IS \'(DC2Type:datetime_utc)\'');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE payment_token');
        $this->addSql('ALTER TABLE invoice ADD payment_token VARCHAR(32) DEFAULT NULL');
    }
}
