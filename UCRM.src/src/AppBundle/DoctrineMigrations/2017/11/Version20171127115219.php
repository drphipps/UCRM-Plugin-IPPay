<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171127115219 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM client_attribute WHERE client_id IS NULL OR attribute_id IS NULL');
        $this->addSql('ALTER TABLE client_attribute ALTER client_id SET NOT NULL');
        $this->addSql('ALTER TABLE client_attribute ALTER attribute_id SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client_attribute ALTER client_id DROP NOT NULL');
        $this->addSql('ALTER TABLE client_attribute ALTER attribute_id DROP NOT NULL');
    }
}
