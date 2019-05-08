<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180119134550 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket_canned_response ALTER name DROP DEFAULT');
        $this->addSql('ALTER TABLE ticket_canned_response ALTER content TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE ticket_canned_response ALTER content DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket_canned_response ALTER name SET DEFAULT \'\'');
        $this->addSql('ALTER TABLE ticket_canned_response ALTER content TYPE TEXT');
        $this->addSql('ALTER TABLE ticket_canned_response ALTER content SET DEFAULT \'\'');
    }
}
