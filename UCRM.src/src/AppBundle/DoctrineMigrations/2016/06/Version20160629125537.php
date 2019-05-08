<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160629125537 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE "option" SET "description" = \'This email address is used to send all UCRM mail messages, i.e. it\'\'s used as the "Sender attribute". Besides this, all UCRM system notifications are sent to this address.\' WHERE "option_id" = 11;');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE "option" SET "description" = \'This will be used only for system notifications sent the administrator email (e.g. when drafts for recurring invoices are created)\' WHERE "option_id" = 11;');
    }
}
