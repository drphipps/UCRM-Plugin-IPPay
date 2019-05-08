<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171122151217 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE user_ticket_group (user_user_id INT NOT NULL, ticket_group_id INT NOT NULL, PRIMARY KEY(user_user_id, ticket_group_id))');
        $this->addSql('CREATE INDEX IDX_C53C5B4CFF63CD9F ON user_ticket_group (user_user_id)');
        $this->addSql('CREATE INDEX IDX_C53C5B4C7B3D25C6 ON user_ticket_group (ticket_group_id)');
        $this->addSql('CREATE TABLE ticket_group (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE ticket_group_assignment (id INT NOT NULL, assigned_group_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8DBA91918359DF4E ON ticket_group_assignment (assigned_group_id)');
        $this->addSql('ALTER TABLE user_ticket_group ADD CONSTRAINT FK_C53C5B4CFF63CD9F FOREIGN KEY (user_user_id) REFERENCES "user" (user_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_ticket_group ADD CONSTRAINT FK_C53C5B4C7B3D25C6 FOREIGN KEY (ticket_group_id) REFERENCES ticket_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_group_assignment ADD CONSTRAINT FK_8DBA91918359DF4E FOREIGN KEY (assigned_group_id) REFERENCES ticket_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_group_assignment ADD CONSTRAINT FK_8DBA9191BF396750 FOREIGN KEY (id) REFERENCES ticket_activity (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket ADD group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3FE54D947 FOREIGN KEY (group_id) REFERENCES ticket_group (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_97A0ADA3FE54D947 ON ticket (group_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE user_ticket_group DROP CONSTRAINT FK_C53C5B4C7B3D25C6');
        $this->addSql('ALTER TABLE ticket DROP CONSTRAINT FK_97A0ADA3FE54D947');
        $this->addSql('ALTER TABLE ticket_group_assignment DROP CONSTRAINT FK_8DBA91918359DF4E');
        $this->addSql('DROP TABLE user_ticket_group');
        $this->addSql('DROP TABLE ticket_group');
        $this->addSql('DROP TABLE ticket_group_assignment');
        $this->addSql('DROP INDEX IDX_97A0ADA3FE54D947');
        $this->addSql('ALTER TABLE ticket DROP group_id');
    }
}
