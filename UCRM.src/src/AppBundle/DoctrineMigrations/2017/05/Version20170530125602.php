<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170530125602 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM client_log WHERE client_id IS NULL');
        $this->addSql('ALTER TABLE client_log DROP CONSTRAINT FK_A89BFB6119EB6921');
        $this->addSql('ALTER TABLE client_log ALTER client_id SET NOT NULL');
        $this->addSql('ALTER TABLE client_log ADD CONSTRAINT FK_A89BFB6119EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client_log DROP CONSTRAINT fk_a89bfb6119eb6921');
        $this->addSql('ALTER TABLE client_log ALTER client_id DROP NOT NULL');
        $this->addSql('ALTER TABLE client_log ADD CONSTRAINT fk_a89bfb6119eb6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
