<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160628134818 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE "option" SET "name" = \'Stop invoicing for suspended services\', "description" = \'If checked, invoicing will be terminated once a service has been suspended. Otherwise the invoicing will continue even for suspended services.\', "value" = 0 WHERE "option_id" = 7;');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE "option" SET "name" = \'Stop invoicing for stopped services\', "description" = \'If checked, invoicing will be terminated once a service has ended (proration will be used for the last billed period). Otherwise the invoicing will continue even for stopped services.\', "value" = 1 WHERE "option_id" = 7;');
    }
}
