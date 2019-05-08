<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160905143705 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE app_key_key_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE app_key (key_id INT NOT NULL, name VARCHAR(256) NOT NULL, key VARCHAR(64) NOT NULL, type VARCHAR(64) NOT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_used_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(key_id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A4E2186E8A90ABA9 ON app_key (key)');
        $this->addSql('COMMENT ON COLUMN app_key.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN app_key.last_used_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (37, 1, \'AppBundle\Controller\AppKeyController\', \'edit\')');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE app_key_key_id_seq CASCADE');
        $this->addSql('DROP TABLE app_key');
        $this->addSql('DELETE FROM user_group_permission WHERE permission_id = 37');
    }
}
