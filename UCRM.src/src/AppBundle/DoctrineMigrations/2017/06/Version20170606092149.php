<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170606092149 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, NULL)',
            [
                90,
                'GOOGLE_OAUTH_SECRET',
            ]
        );
        $this->addSql('ALTER TABLE "user" ADD google_oauth_token TEXT DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM option WHERE option_id = 90');
        $this->addSql('ALTER TABLE "user" DROP google_oauth_token');
    }
}
