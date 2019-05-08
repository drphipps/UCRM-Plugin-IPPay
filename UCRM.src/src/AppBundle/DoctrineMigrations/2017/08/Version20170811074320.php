<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170811074320 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE job_attachment (id SERIAL NOT NULL, job_id INT DEFAULT NULL, filename VARCHAR(50) NOT NULL, original_filename VARCHAR(255) NOT NULL, mime_type VARCHAR(50) NOT NULL, size INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_132849A6BE04EA9 ON job_attachment (job_id)');
        $this->addSql('ALTER TABLE job_attachment ADD CONSTRAINT FK_132849A6BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE job_attachment');
    }
}
