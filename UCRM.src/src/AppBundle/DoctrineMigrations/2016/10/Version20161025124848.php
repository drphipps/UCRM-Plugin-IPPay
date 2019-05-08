<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161025124848 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device ADD net_flow_active_version SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE device ADD net_flow_pending_version SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE device RENAME COLUMN netflow_log TO net_flow_log');
        $this->addSql('ALTER TABLE device RENAME COLUMN netflow_synchronized TO net_flow_synchronized');

        $this->addSql('UPDATE device SET net_flow_active_version = netflow_enabled WHERE netflow_enabled IN (5, 9)');
        $this->addSql('UPDATE device SET net_flow_log = NULL WHERE netflow_enabled = 1 OR net_flow_synchronized = false');

        $this->addSql('ALTER TABLE device DROP netflow_enabled');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE device ADD netflow_enabled INT DEFAULT 0 NOT NULL');

        $this->addSql('UPDATE device SET netflow_enabled = net_flow_active_version WHERE net_flow_active_version IN (5, 9)');

        $this->addSql('ALTER TABLE device DROP net_flow_active_version');
        $this->addSql('ALTER TABLE device DROP net_flow_pending_version');
        $this->addSql('ALTER TABLE device RENAME COLUMN net_flow_log TO netflow_log');
        $this->addSql('ALTER TABLE device RENAME COLUMN net_flow_synchronized TO netflow_synchronized');
    }
}
