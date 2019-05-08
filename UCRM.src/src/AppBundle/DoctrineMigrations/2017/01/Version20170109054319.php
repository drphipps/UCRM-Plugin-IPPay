<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170109054319 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client ADD user_ident_int BIGINT DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN client.user_ident_int IS \'User id casted to integer for ordering\'');
        $this->addSql('CREATE INDEX IDX_C7440455DDD457C4E68C83EA ON client (user_ident_int, user_ident)');
    }

    public function postUp(Schema $schema)
    {
        $statement = $this->connection->query('SELECT client_id, user_ident FROM client');
        while ($row = $statement->fetch()) {
            $this->connection->update(
                'client',
                [
                    'user_ident_int' => ctype_digit($row['user_ident']) && bccomp($row['user_ident'], PHP_INT_MAX) !== 1
                        ? (int) $row['user_ident']
                        : null,
                ],
                [
                    'client_id' => $row['client_id'],
                ]
            );
        }
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX IDX_C7440455DDD457C4E68C83EA');
        $this->addSql('ALTER TABLE client DROP user_ident_int');
    }
}
