<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170810085716 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO currency (currency_id, name, code, symbol) VALUES (119, \'Central African CFA franc\', \'XAF\', \'FCFA\')');
        $this->addSql('INSERT INTO currency (currency_id, name, code, symbol) VALUES (120, \'West African CFA franc\', \'XOF\', \'CFA\')');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DELETE FROM currency WHERE currency_id IN (119, 120)');
    }
}
