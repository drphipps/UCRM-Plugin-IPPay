<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170529073435 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE tax SET selected = FALSE WHERE selected IS NULL');
        $this->addSql('ALTER TABLE tax ALTER selected SET DEFAULT \'false\'');
        $this->addSql('ALTER TABLE tax ALTER selected SET NOT NULL');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE tax ALTER selected DROP DEFAULT');
        $this->addSql('ALTER TABLE tax ALTER selected DROP NOT NULL');
    }
}
