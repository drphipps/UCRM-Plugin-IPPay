<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160719104607 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE user_group_special_permission_special_permission_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE user_group_special_permission (special_permission_id INT NOT NULL, group_id INT DEFAULT NULL, module_name VARCHAR(255) NOT NULL, permission VARCHAR(20) DEFAULT \'deny\' NOT NULL, PRIMARY KEY(special_permission_id))');
        $this->addSql('CREATE INDEX IDX_374D3E79FE54D947 ON user_group_special_permission (group_id)');
        $this->addSql('ALTER TABLE user_group_special_permission ADD CONSTRAINT FK_374D3E79FE54D947 FOREIGN KEY (group_id) REFERENCES user_group (group_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE user_group_special_permission_special_permission_id_seq CASCADE');
        $this->addSql('DROP TABLE user_group_special_permission');
    }
}
