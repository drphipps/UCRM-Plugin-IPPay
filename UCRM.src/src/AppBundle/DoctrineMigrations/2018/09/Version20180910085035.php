<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180910085035 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql('ALTER TABLE "user" ADD full_name VARCHAR(510) DEFAULT NULL');
        $this->addSql(
            '
                UPDATE "user" 
                SET full_name = 
                    CASE WHEN trim(concat(first_name, \' \', last_name)) != \'\' THEN
                        trim(concat(first_name, \' \', last_name))
                    ELSE
                        username
                    END
                '
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql('ALTER TABLE "user" DROP full_name');
    }
}
