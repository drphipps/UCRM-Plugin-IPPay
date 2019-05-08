<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170913141330 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE option SET code = \'FORMAT_DATE_ALTERNATIVE\' WHERE code = \'FORMAT_DATE_SHORT\'');
        $this->addSql('UPDATE option SET code = \'FORMAT_DATE_DEFAULT\' WHERE code = \'FORMAT_DATE\'');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE option SET code = \'FORMAT_DATE_SHORT\' WHERE code = \'FORMAT_DATE_ALTERNATIVE\'');
        $this->addSql('UPDATE option SET code = \'FORMAT_DATE\' WHERE code = \'FORMAT_DATE_DEFAULT\'');
    }
}
