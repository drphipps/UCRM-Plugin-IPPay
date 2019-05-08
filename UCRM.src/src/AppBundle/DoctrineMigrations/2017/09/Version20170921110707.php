<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170921110707 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log ADD "address_from" VARCHAR(320)');
        $this->addSql('UPDATE email_log SET  "address_from" = "sender"');
        $this->addSql('ALTER TABLE email_log ALTER "address_from" SET NOT NULL');
        $this->addSql('ALTER TABLE email_log ALTER "sender" DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE email_log DROP "address_from"');
        $this->addSql('ALTER TABLE email_log ALTER "sender" SET NOT NULL');
    }
}
