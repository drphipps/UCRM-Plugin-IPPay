<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use AppBundle\Util\AvatarColors;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180406105432 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE "user" ADD avatar_color VARCHAR(7) DEFAULT NULL');
        $this->addSql(
            sprintf(
                'UPDATE "user" SET avatar_color = %s',
                AvatarColors::getRandomSQL()
            )
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE "user" DROP avatar_color');
    }
}
