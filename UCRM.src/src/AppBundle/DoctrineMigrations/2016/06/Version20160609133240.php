<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160609133240 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device ALTER login_username TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE device ALTER login_password TYPE TEXT');
        $this->addSql('ALTER TABLE device ALTER login_password DROP DEFAULT');
        $this->addSql('ALTER TABLE device ALTER login_password TYPE TEXT');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device ALTER login_username TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE device ALTER login_password TYPE VARCHAR(64)');
        $this->addSql('ALTER TABLE device ALTER login_password DROP DEFAULT');
    }
}
