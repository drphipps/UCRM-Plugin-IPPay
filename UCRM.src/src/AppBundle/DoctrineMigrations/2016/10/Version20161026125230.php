<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161026125230 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE document (document_id SERIAL NOT NULL, user_id INT DEFAULT NULL, client_id INT NOT NULL, name VARCHAR(256) NOT NULL, type VARCHAR(64) NOT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, path VARCHAR(1024) NOT NULL, size BIGINT NOT NULL, PRIMARY KEY(document_id))');
        $this->addSql('CREATE INDEX IDX_D8698A76A76ED395 ON document (user_id)');
        $this->addSql('CREATE INDEX IDX_D8698A7619EB6921 ON document (client_id)');
        $this->addSql('COMMENT ON COLUMN document.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A7619EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (38, 1, \'AppBundle\Controller\DocumentController\', \'edit\')');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE document');
        $this->addSql('DELETE FROM user_group_permission WHERE permission_id = 38');
    }
}
