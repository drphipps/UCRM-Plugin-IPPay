<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170227124243 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE header_notification (id SERIAL NOT NULL, type SMALLINT DEFAULT 1 NOT NULL, title VARCHAR(255) NOT NULL, link VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN header_notification.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('CREATE TABLE header_notification_status (id SERIAL NOT NULL, header_notification_id INT NOT NULL, user_id INT NOT NULL, read BOOLEAN DEFAULT \'false\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B3FC8A5FB8F55353 ON header_notification_status (header_notification_id)');
        $this->addSql('CREATE INDEX IDX_B3FC8A5FA76ED395 ON header_notification_status (user_id)');
        $this->addSql('ALTER TABLE header_notification_status ADD CONSTRAINT FK_B3FC8A5FB8F55353 FOREIGN KEY (header_notification_id) REFERENCES header_notification (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE header_notification_status ADD CONSTRAINT FK_B3FC8A5FA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE header_notification_status DROP CONSTRAINT FK_B3FC8A5FB8F55353');
        $this->addSql('DROP TABLE header_notification');
        $this->addSql('DROP TABLE header_notification_status');
    }
}
