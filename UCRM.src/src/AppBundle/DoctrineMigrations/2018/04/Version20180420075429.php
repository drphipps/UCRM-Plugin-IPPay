<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180420075429 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        // If Client has not "Billing" email set, all emails are set to "Billing". This is same as current fallback.
        $this->addSql('
        INSERT INTO client_contact_contact_type (client_contact_client_contact_id, contact_type_id)
        SELECT DISTINCT c1.client_contact_id, 1
        FROM client_contact c1
        LEFT JOIN client_contact_contact_type c2 ON c1.client_contact_id = c2.client_contact_client_contact_id
        WHERE (c2.contact_type_id != 1 OR c2.contact_type_id IS NULL)
        AND c1.client_id NOT IN (
            SELECT c3.client_id
            FROM client_contact c3
            LEFT JOIN client_contact_contact_type c4 ON c1.client_contact_id = c4.client_contact_client_contact_id
            WHERE c4.contact_type_id = 1
          )
        ');

        // Same, but for "Contact" email type.
        $this->addSql('
        INSERT INTO client_contact_contact_type (client_contact_client_contact_id, contact_type_id)
        SELECT DISTINCT c1.client_contact_id, 2
        FROM client_contact c1
        LEFT JOIN client_contact_contact_type c2 ON c1.client_contact_id = c2.client_contact_client_contact_id
        WHERE (c2.contact_type_id != 2 OR c2.contact_type_id IS NULL)
        AND c1.client_id NOT IN (
            SELECT c3.client_id
            FROM client_contact c3
            LEFT JOIN client_contact_contact_type c4 ON c1.client_contact_id = c4.client_contact_client_contact_id
            WHERE c4.contact_type_id = 2
          )
        ');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
    }
}
