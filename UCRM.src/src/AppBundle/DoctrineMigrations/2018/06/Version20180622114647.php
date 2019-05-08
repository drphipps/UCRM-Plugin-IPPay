<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180622114647 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE csv_import (id SERIAL NOT NULL, user_id INT DEFAULT NULL, uuid UUID NOT NULL, created_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, count INT DEFAULT 0 NOT NULL, count_success INT DEFAULT 0 NOT NULL, count_failure INT DEFAULT 0 NOT NULL, type VARCHAR(20) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_835A46E2D17F50A6 ON csv_import (uuid)');
        $this->addSql('CREATE INDEX IDX_835A46E2A76ED395 ON csv_import (user_id)');
        $this->addSql('COMMENT ON COLUMN csv_import.created_date IS \'(DC2Type:datetime_utc)\'');
        $this->addSql('ALTER TABLE csv_import ADD CONSTRAINT FK_835A46E2A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE header_notification ADD uuid UUID DEFAULT NULL');
        $this->addSql('UPDATE header_notification SET uuid = uuid_generate_v4()');
        $this->addSql('ALTER TABLE header_notification ALTER uuid SET NOT NULL');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_32E4644DD17F50A6 ON header_notification (uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE csv_import');
        $this->addSql('DROP INDEX UNIQ_32E4644DD17F50A6');
        $this->addSql('ALTER TABLE header_notification DROP uuid');
    }
}
