<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20161221150240 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE download (download_id SERIAL NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, path VARCHAR(255) DEFAULT NULL, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, generated TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status SMALLINT NOT NULL, PRIMARY KEY(download_id))');
        $this->addSql('CREATE INDEX IDX_781A8270A76ED395 ON download (user_id)');
        $this->addSql('COMMENT ON COLUMN download.created IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('COMMENT ON COLUMN download.generated IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE download ADD CONSTRAINT FK_781A8270A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (42, 1, \'AppBundle\Controller\DownloadController\', \'edit\')');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE download');

        $this->addSql('DELETE FROM user_group_permission WHERE permission_id=42');
    }
}
