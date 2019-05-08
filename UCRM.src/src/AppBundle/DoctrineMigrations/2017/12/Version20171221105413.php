<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171221105413 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('
            INSERT INTO general
            (code, value)
            SELECT \'appearance_login_banner\', (SELECT login_banner FROM organization WHERE selected = TRUE)
        ');

        $this->addSql('ALTER TABLE organization DROP login_banner');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM general WHERE code = \'appearance_login_banner\'');
        $this->addSql('ALTER TABLE organization ADD login_banner VARCHAR(255) DEFAULT NULL');
    }
}
