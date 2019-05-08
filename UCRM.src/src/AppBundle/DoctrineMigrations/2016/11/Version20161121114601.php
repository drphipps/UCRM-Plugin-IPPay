<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20161121114601 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE device_ip (ip_id SERIAL NOT NULL, nat_public_ip BIGINT DEFAULT NULL, was_last_connection_successful BOOLEAN DEFAULT \'false\' NOT NULL, ip_address BIGINT NOT NULL, netmask SMALLINT DEFAULT NULL, first_ip_address BIGINT NOT NULL, last_ip_address BIGINT NOT NULL, PRIMARY KEY(ip_id))');
        $this->addSql('ALTER TABLE device ADD search_ip INT DEFAULT NULL');
        $this->addSql('ALTER TABLE device ADD CONSTRAINT FK_92FB68E7FDDB4D4 FOREIGN KEY (search_ip) REFERENCES device_ip (ip_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_92FB68E7FDDB4D4 ON device (search_ip)');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device DROP CONSTRAINT FK_92FB68E7FDDB4D4');
        $this->addSql('DROP TABLE device_ip');
        $this->addSql('DROP INDEX UNIQ_92FB68E7FDDB4D4');
        $this->addSql('ALTER TABLE device DROP search_ip');
    }
}
