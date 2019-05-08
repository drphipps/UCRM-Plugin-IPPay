<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170711142737 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE user_personalization (id SERIAL NOT NULL, dashboard_show_overview BOOLEAN DEFAULT \'true\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE "user" ADD user_personalization_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D6493787F98A FOREIGN KEY (user_personalization_id) REFERENCES user_personalization (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6493787F98A ON "user" (user_personalization_id)');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D6493787F98A');
        $this->addSql('DROP TABLE user_personalization');
        $this->addSql('DROP INDEX UNIQ_8D93D6493787F98A');
        $this->addSql('ALTER TABLE "user" DROP user_personalization_id');
    }
}
