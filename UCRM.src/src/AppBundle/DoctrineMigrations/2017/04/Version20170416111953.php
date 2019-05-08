<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170416111953 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE "user" ADD locale_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT fk_user_locale FOREIGN KEY (locale_id) REFERENCES locale(locale_id)');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT fk_user_locale');
        $this->addSql('ALTER TABLE "user" DROP COLUMN locale_id');
    }
}
