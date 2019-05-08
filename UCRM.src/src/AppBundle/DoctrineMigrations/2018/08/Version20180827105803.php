<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180827105803 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'UPDATE currency SET name = ?, symbol = ? WHERE code = ?',
            [
                'Indian rupee',
                '₹',
                'INR',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');

        $this->addSql(
            'UPDATE currency SET name = ?, symbol = ? WHERE code = ?',
            [
                'Rupees',
                'Rp',
                'INR',
            ]
        );
    }
}
