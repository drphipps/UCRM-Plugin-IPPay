<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181123085525 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $distinctValueCount = (int) $this->connection->fetchColumn(
            'SELECT COUNT(DISTINCT send_emails_automatically) FROM service'
        );

        $this->addSql('ALTER TABLE service ALTER send_emails_automatically DROP DEFAULT');
        $this->addSql('ALTER TABLE service ALTER send_emails_automatically DROP NOT NULL');

        // If the config is the same everywhere and matches global settings, use global settings everywhere.
        if ($distinctValueCount === 1) {
            $this->addSql(
                '
                  UPDATE service
                  SET send_emails_automatically = NULL
                  WHERE send_emails_automatically = (
                    SELECT COALESCE(NULLIF(value, \'\'), \'f\')::boolean FROM option WHERE code = ?
                  )
                ',
                [
                    'SEND_INVOICE_BY_EMAIL',
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service ALTER send_emails_automatically SET DEFAULT \'false\'');
        $this->addSql(
            '
              UPDATE service
              SET send_emails_automatically = (
                SELECT COALESCE(NULLIF(value, \'\'), \'f\')::boolean FROM option WHERE code = ?
              )
              WHERE send_emails_automatically IS NULL
            ',
            [
                'SEND_INVOICE_BY_EMAIL',
            ]
        );
        $this->addSql('ALTER TABLE service ALTER send_emails_automatically SET NOT NULL');
    }
}
