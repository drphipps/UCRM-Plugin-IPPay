<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180412110101 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE ticket_job_assignment (id INT NOT NULL, assigned_job_id INT DEFAULT NULL, type VARCHAR(20) DEFAULT \'add\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A34ACF6493F6E627 ON ticket_job_assignment (assigned_job_id)');
        $this->addSql('ALTER TABLE ticket_job_assignment ADD CONSTRAINT FK_A34ACF6493F6E627 FOREIGN KEY (assigned_job_id) REFERENCES job (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_job_assignment ADD CONSTRAINT FK_A34ACF64BF396750 FOREIGN KEY (id) REFERENCES ticket_activity (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_user_assignment DROP CONSTRAINT fk_a656d6eeadf66b1a');
        $this->addSql('ALTER TABLE ticket_user_assignment ADD type VARCHAR(20) DEFAULT \'add\' NOT NULL');
        $this->addSql('ALTER TABLE ticket_user_assignment ADD CONSTRAINT FK_9536E947ADF66B1A FOREIGN KEY (assigned_user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_client_assignment DROP CONSTRAINT FK_2928E0AF604ADC83');
        $this->addSql('ALTER TABLE ticket_client_assignment ADD type VARCHAR(20) DEFAULT \'add\' NOT NULL');
        $this->addSql('ALTER TABLE ticket_client_assignment ADD CONSTRAINT FK_2928E0AF604ADC83 FOREIGN KEY (assigned_client_id) REFERENCES client (client_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_group_assignment DROP CONSTRAINT FK_8DBA91918359DF4E');
        $this->addSql('ALTER TABLE ticket_group_assignment ADD type VARCHAR(20) DEFAULT \'add\' NOT NULL');
        $this->addSql('ALTER TABLE ticket_group_assignment ADD CONSTRAINT FK_8DBA91918359DF4E FOREIGN KEY (assigned_group_id) REFERENCES ticket_group (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('
            UPDATE
              ticket_group_assignment
            SET
              type = \'delete\'
            WHERE
              assigned_group_id IS NULL
        ');

        $this->addSql('
            UPDATE
              ticket_user_assignment
            SET
              type = \'delete\'
            WHERE
              assigned_user_id IS NULL
        ');

        $this->addSql('
            UPDATE
              ticket_client_assignment
            SET
              type = \'delete\'
            WHERE
              assigned_client_id IS NULL
        ');

        $this->addSql('
            INSERT INTO ticket_group_assignment
              (assigned_group_id, type, id)
            SELECT
              null,
              \'add\',
              ta.id
            FROM
              ticket_activity ta
            LEFT JOIN 
              ticket_group_assignment tga ON tga.id = ta.id
            WHERE 
              ta.dtype = \'ticketgroupassignment\'
              AND tga.id IS NULL
        ');

        $this->addSql('
            INSERT INTO ticket_user_assignment
              (assigned_user_id, type, id)
            SELECT
              null,
              \'add\',
              ta.id
            FROM
              ticket_activity ta
            LEFT JOIN 
              ticket_user_assignment tua ON tua.id = ta.id
            WHERE 
              ta.dtype = \'ticketuserassignment\'
              AND tua.id IS NULL
        ');

        $this->addSql('
            INSERT INTO ticket_client_assignment
              (assigned_client_id, type, id)
            SELECT
              null,
              \'add\',
              ta.id
            FROM
              ticket_activity ta
            LEFT JOIN 
              ticket_client_assignment tca ON tca.id = ta.id
            WHERE 
              ta.dtype = \'ticketclientassignment\'
              AND tca.id IS NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE ticket_job_assignment');
        $this->addSql('ALTER TABLE ticket_user_assignment DROP CONSTRAINT FK_9536E947ADF66B1A');
        $this->addSql('ALTER TABLE ticket_user_assignment DROP type');
        $this->addSql('ALTER TABLE ticket_user_assignment ADD CONSTRAINT fk_a656d6eeadf66b1a FOREIGN KEY (assigned_user_id) REFERENCES "user" (user_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_client_assignment DROP CONSTRAINT fk_2928e0af604adc83');
        $this->addSql('ALTER TABLE ticket_client_assignment DROP type');
        $this->addSql('ALTER TABLE ticket_client_assignment ADD CONSTRAINT fk_2928e0af604adc83 FOREIGN KEY (assigned_client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_group_assignment DROP CONSTRAINT fk_8dba91918359df4e');
        $this->addSql('ALTER TABLE ticket_group_assignment DROP type');
        $this->addSql('ALTER TABLE ticket_group_assignment ADD CONSTRAINT fk_8dba91918359df4e FOREIGN KEY (assigned_group_id) REFERENCES ticket_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
