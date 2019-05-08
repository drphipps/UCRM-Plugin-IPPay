<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160531130333 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice_send_email DROP CONSTRAINT FK_772C09AC2989F1FD');
        $this->addSql('ALTER TABLE invoice_send_email ADD CONSTRAINT FK_772C09AC2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (invoice_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice_send_email DROP CONSTRAINT fk_772c09ac2989f1fd');
        $this->addSql('ALTER TABLE invoice_send_email ADD CONSTRAINT fk_772c09ac2989f1fd FOREIGN KEY (invoice_id) REFERENCES invoice (invoice_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
