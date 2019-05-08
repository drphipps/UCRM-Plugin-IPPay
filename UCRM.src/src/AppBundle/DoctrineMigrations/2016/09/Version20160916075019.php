<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160916075019 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS citext;');

        $query = '
            SELECT 1
            FROM "user"
            WHERE "%s" IS NOT NULL
            GROUP BY LOWER("%s")
            HAVING COUNT("user_id") > 1
        ';

        if (! $this->connection->fetchColumn(sprintf($query, 'username', 'username'))) {
            $this->addSql('ALTER TABLE "user" ALTER "username" TYPE citext;');
        }
        if (! $this->connection->fetchColumn(sprintf($query, 'email1', 'email1'))) {
            $this->addSql('ALTER TABLE "user" ALTER "email1" TYPE citext;');
        }
        if (! $this->connection->fetchColumn(sprintf($query, 'email2', 'email2'))) {
            $this->addSql('ALTER TABLE "user" ALTER "email2" TYPE citext;');
        }
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE "user" ALTER "username" TYPE character varying, ALTER "email1" TYPE character varying, ALTER "email2" TYPE character varying;');
        $this->addSql('DROP EXTENSION IF EXISTS citext;');
    }
}
