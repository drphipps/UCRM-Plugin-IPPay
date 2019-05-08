<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181114085227 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM job_attachment WHERE job_id IS NULL');
        $this->addSql('ALTER TABLE job_attachment DROP CONSTRAINT FK_132849A6BE04EA9');
        $this->addSql('ALTER TABLE job_attachment ALTER job_id SET NOT NULL');
        $this->addSql('ALTER TABLE job_attachment ADD CONSTRAINT FK_132849A6BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE job_attachment DROP CONSTRAINT fk_132849a6be04ea9');
        $this->addSql('ALTER TABLE job_attachment ALTER job_id DROP NOT NULL');
        $this->addSql('ALTER TABLE job_attachment ADD CONSTRAINT fk_132849a6be04ea9 FOREIGN KEY (job_id) REFERENCES job (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
