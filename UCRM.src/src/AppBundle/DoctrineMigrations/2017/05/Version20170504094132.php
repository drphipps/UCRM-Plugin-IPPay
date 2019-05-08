<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170504094132 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE custom_attribute (id SERIAL NOT NULL, key VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B040985D8A90ABA9 ON custom_attribute (key)');
        $this->addSql('CREATE TABLE client_attribute (id SERIAL NOT NULL, client_id INT DEFAULT NULL, attribute_id INT DEFAULT NULL, value TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_115A3FF419EB6921 ON client_attribute (client_id)');
        $this->addSql('CREATE INDEX IDX_115A3FF4B6E62EFA ON client_attribute (attribute_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_115A3FF419EB6921B6E62EFA ON client_attribute (client_id, attribute_id)');
        $this->addSql('ALTER TABLE client_attribute ADD CONSTRAINT FK_115A3FF419EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE client_attribute ADD CONSTRAINT FK_115A3FF4B6E62EFA FOREIGN KEY (attribute_id) REFERENCES custom_attribute (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invoice ADD client_attributes JSON DEFAULT \'[]\' NOT NULL');
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (47, 1, \'AppBundle\Controller\CustomAttributeController\', \'edit\')');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client_attribute DROP CONSTRAINT FK_115A3FF4B6E62EFA');
        $this->addSql('DROP TABLE custom_attribute');
        $this->addSql('DROP TABLE client_attribute');
        $this->addSql('ALTER TABLE invoice DROP client_attributes');
        $this->addSql('DELETE FROM user_group_permission WHERE permission_id = 47');
    }
}
