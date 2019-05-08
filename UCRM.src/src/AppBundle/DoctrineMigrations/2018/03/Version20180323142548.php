<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180323142548 extends AbstractMigration
{
    public function isTransactional()
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        try {
            $this->connection->transactional(function (Connection $connection) {
                $connection->exec('
                  ALTER TABLE credit
                  ADD CONSTRAINT credit__positive_amount_check
                    CHECK (amount >= 0)'
                );
            });
        } catch (\Exception $exception) {
            // ignore
        }

        try {
            $this->connection->transactional(function (Connection $connection) {
                $connection->exec('
                  ALTER TABLE payment_cover
                  ADD CONSTRAINT payment_cover__positive_amount_check
                    CHECK (amount >= 0)'
                );
            });
        } catch (\Exception $exception) {
            // ignore
        }

        $this->addSql('SELECT 1');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE credit DROP CONSTRAINT IF EXISTS credit__positive_amount_check');
        $this->addSql('ALTER TABLE payment_cover DROP CONSTRAINT IF EXISTS payment_cover__positive_amount_check');
    }
}
