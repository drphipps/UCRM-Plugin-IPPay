<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171221120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO locale (locale_id, code, name) VALUES (?, ?, ?)', [12, 'da_DK', 'Danish']);
        $this->addSql('INSERT INTO locale (locale_id, code, name) VALUES (?, ?, ?)', [13, 'sk_SK', 'Slovak']);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM locale WHERE locale_id IN (12, 13)');
    }
}
