<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170612100425 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client_log DROP CONSTRAINT FK_A89BFB6119EB6921');
        $this->addSql('ALTER TABLE client_log ADD CONSTRAINT FK_A89BFB6119EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client_log DROP CONSTRAINT fk_a89bfb6119eb6921');
        $this->addSql('ALTER TABLE client_log ADD CONSTRAINT fk_a89bfb6119eb6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
