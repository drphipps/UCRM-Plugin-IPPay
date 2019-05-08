<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171005135839 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket ADD last_activity TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN ticket.last_activity IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('
            UPDATE ticket
            SET last_activity = tt.max_last_activity 
            FROM
            (
                SELECT MAX(ta.created_at) AS max_last_activity, ta.ticket_id
                FROM ticket_activity ta
                INNER JOIN ticket jt
                ON jt.id = ta.ticket_id
                GROUP BY ta.ticket_id
            ) tt
            WHERE tt.ticket_id = ticket.id
        ');
        $this->addSql('ALTER TABLE ticket ALTER last_activity SET NOT NULL');
        $this->addSql('CREATE TABLE ticket_status_change (id INT NOT NULL, status SMALLINT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE ticket_status_change ADD CONSTRAINT FK_BCD9FAF1BF396750 FOREIGN KEY (id) REFERENCES ticket_activity (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE ticket_client_assignment (id INT NOT NULL, assigned_client_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2928E0AF604ADC83 ON ticket_client_assignment (assigned_client_id)');
        $this->addSql('ALTER TABLE ticket_assignment RENAME TO ticket_user_assignment');
        $this->addSql('ALTER TABLE ticket_client_assignment ADD CONSTRAINT FK_2928E0AF604ADC83 FOREIGN KEY (assigned_client_id) REFERENCES client (client_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_client_assignment ADD CONSTRAINT FK_2928E0AFBF396750 FOREIGN KEY (id) REFERENCES ticket_activity (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_a656d6eeadf66b1a RENAME TO IDX_9536E947ADF66B1A');
        $this->addSql(
            'UPDATE ticket_activity SET dtype = ? WHERE dtype = ?',
            [
                'ticketuserassignment',
                'ticketassignment',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket DROP last_activity');
        $this->addSql('DROP TABLE ticket_status_change');
        $this->addSql('DROP TABLE ticket_client_assignment');
        $this->addSql('ALTER TABLE ticket_user_assignment RENAME TO ticket_assignment');
        $this->addSql('ALTER INDEX idx_9536e947adf66b1a RENAME TO idx_a656d6eeadf66b1a');
    }
}
