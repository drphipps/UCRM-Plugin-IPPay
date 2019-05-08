<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170116112008 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            '
                UPDATE invoice
                SET currency_id = (
                    SELECT organization.currency_id
                    FROM organization
                    INNER JOIN client
                    ON client.organization_id = organization.organization_id
                    WHERE invoice.client_id = client.client_id
                )
                WHERE currency_id IS NULL
            '
        );
        $this->addSql('ALTER TABLE invoice ALTER currency_id SET NOT NULL');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE invoice ALTER currency_id DROP NOT NULL');
    }
}
