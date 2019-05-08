<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160502160335 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE general_general_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE general (general_id INT NOT NULL, code VARCHAR(40) NOT NULL, value VARCHAR(500) DEFAULT NULL, PRIMARY KEY(general_id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CE29364A77153098 ON general (code)');
        $this->addSql('INSERT INTO general (general_id, code, value) VALUES (1, \'crm_installed_version\', \'0.0.0\')');
        $this->addSql('INSERT INTO general (general_id, code, value) VALUES (2, \'crm_latest_version\', \'0.0.0\')');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE general_general_id_seq CASCADE');
        $this->addSql('DROP TABLE general');
    }
}
