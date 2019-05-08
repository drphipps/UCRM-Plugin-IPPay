<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170803091950 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE user_authentication_key (id SERIAL NOT NULL, user_id INT NOT NULL, key VARCHAR(64) NOT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_used_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, expiration INT NOT NULL, sliding BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5B6183BA8A90ABA9 ON user_authentication_key (key)');
        $this->addSql('COMMENT ON COLUMN user_authentication_key.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN user_authentication_key.last_used_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE user_authentication_key ADD CONSTRAINT FK_5B6183BAA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE user_authentication_key');
    }
}
