<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170623123029 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE client_client_tag (client_client_id INT NOT NULL, client_tag_id INT NOT NULL, PRIMARY KEY(client_client_id, client_tag_id))');
        $this->addSql('CREATE INDEX IDX_B4791966F2CBB92E ON client_client_tag (client_client_id)');
        $this->addSql('CREATE INDEX IDX_B4791966EF7428A2 ON client_client_tag (client_tag_id)');
        $this->addSql('CREATE TABLE client_tag (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, color_background VARCHAR(7) DEFAULT NULL, color_text VARCHAR(7) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE client_client_tag ADD CONSTRAINT FK_B4791966F2CBB92E FOREIGN KEY (client_client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE client_client_tag ADD CONSTRAINT FK_B4791966EF7428A2 FOREIGN KEY (client_tag_id) REFERENCES client_tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (50, 1, \'AppBundle\Controller\ClientTagController\', \'edit\')');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client_client_tag DROP CONSTRAINT FK_B4791966EF7428A2');
        $this->addSql('DROP TABLE client_client_tag');
        $this->addSql('DROP TABLE client_tag');
        $this->addSql('DELETE FROM user_group_permission WHERE permission_id = 50');
    }
}
