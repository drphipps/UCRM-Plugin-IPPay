<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170608084821 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE "user" ADD google_calendar_id VARCHAR(1024) DEFAULT NULL');
        $this->addSql('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');
        $this->addSql('ALTER TABLE job ADD uuid UUID DEFAULT NULL');
        $this->addSql('UPDATE job SET uuid = uuid_generate_v4()');
        $this->addSql('ALTER TABLE job ALTER uuid SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FBD8E0F8D17F50A6 ON job (uuid)');
        $this->addSql('ALTER TABLE "user" ADD next_google_calendar_synchronization TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN "user".next_google_calendar_synchronization IS \'(DC2Type:datetime_utc)\'');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE "user" DROP google_calendar_id');
        $this->addSql('DROP INDEX UNIQ_FBD8E0F8D17F50A6');
        $this->addSql('ALTER TABLE job DROP uuid');
        $this->addSql('DROP EXTENSION IF EXISTS "uuid-ossp";');
        $this->addSql('ALTER TABLE "user" DROP next_google_calendar_synchronization');
    }
}
