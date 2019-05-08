<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170505081122 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE job (id SERIAL NOT NULL, assigned_user_id INT DEFAULT NULL, client_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, duration INT DEFAULT NULL, status SMALLINT DEFAULT 0 NOT NULL, address VARCHAR(255) DEFAULT NULL, gps_lat VARCHAR(50) DEFAULT NULL, gps_lon VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FBD8E0F8ADF66B1A ON job (assigned_user_id)');
        $this->addSql('CREATE INDEX IDX_FBD8E0F819EB6921 ON job (client_id)');
        $this->addSql('COMMENT ON COLUMN job.date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('CREATE TABLE job_comment (id SERIAL NOT NULL, user_id INT DEFAULT NULL, job_id INT NOT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, message TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E9BE5F7A76ED395 ON job_comment (user_id)');
        $this->addSql('CREATE INDEX IDX_E9BE5F7BE04EA9 ON job_comment (job_id)');
        $this->addSql('COMMENT ON COLUMN job_comment.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE job ADD CONSTRAINT FK_FBD8E0F8ADF66B1A FOREIGN KEY (assigned_user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE job ADD CONSTRAINT FK_FBD8E0F819EB6921 FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE job_comment ADD CONSTRAINT FK_E9BE5F7A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE job_comment ADD CONSTRAINT FK_E9BE5F7BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (48, 1, \'SchedulingPermissionsVoter::JOBS_ALL\', \'edit\')');
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (49, 1, \'SchedulingPermissionsVoter::JOBS_MY\', \'edit\')');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE job_comment DROP CONSTRAINT FK_E9BE5F7BE04EA9');
        $this->addSql('DROP TABLE job');
        $this->addSql('DROP TABLE job_comment');
        $this->addSql('DELETE FROM user_group_permission WHERE permission_id IN (48, 49)');
    }
}
