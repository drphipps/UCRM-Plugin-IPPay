<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180622130554 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE webhook_event ALTER uuid TYPE UUID USING uuid::UUID');
        $this->addSql('ALTER TABLE webhook_event ALTER uuid DROP DEFAULT');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B17EEFDED17F50A6 ON webhook_event (uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX UNIQ_B17EEFDED17F50A6');
        $this->addSql('ALTER TABLE webhook_event ALTER uuid TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE webhook_event ALTER uuid DROP DEFAULT');
    }
}
