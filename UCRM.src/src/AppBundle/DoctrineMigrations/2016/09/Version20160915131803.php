<?php

namespace AppBundle\Migrations;

use AppBundle\Entity\Currency;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160915131803 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('UPDATE "organization" SET "currency_id" = ? WHERE "currency_id" IS NULL;', [Currency::DEFAULT_ID]);
        $this->addSql('ALTER TABLE "organization" ALTER "currency_id" SET NOT NULL;');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE "organization" ALTER "currency_id" SET NULL;');
    }
}
