<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180111194124 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('
            DO $$ 
                BEGIN
                    BEGIN
                        ALTER TABLE "user" ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;
                    EXCEPTION
                        WHEN duplicate_column THEN NULL;
                    END;
                END;
            $$
        ');
        $this->addSql('UPDATE "user" SET created_at = COALESCE(last_login, \'1900-01-01\') WHERE created_at IS NULL AND role IN (\'ROLE_SUPER_ADMIN\', \'ROLE_ADMIN\', \'ROLE_WIZARD\')');
        $this->addSql('
            UPDATE "user" u SET created_at = c.registration_date
            FROM client c 
            WHERE u.user_id = c.user_id AND created_at IS NULL
            '
        );
        $this->addSql('UPDATE "user" SET created_at = \'1900-01-01\' WHERE created_at IS NULL');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN created_at SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE "user" DROP created_at');
    }
}
