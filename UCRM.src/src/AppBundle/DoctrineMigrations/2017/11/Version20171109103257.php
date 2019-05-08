<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171109103257 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE client_contact SET email = NULL WHERE email = \'\'');
        $this->addSql('UPDATE client_contact SET phone = NULL WHERE phone = \'\'');
        $this->addSql('UPDATE invoice SET client_email = NULL WHERE client_email = \'\'');
        $this->addSql('UPDATE option SET value = NULL WHERE code = \'MAILER_SENDER_ADDRESS\' AND value = \'\'');
    }

    public function down(Schema $schema): void
    {
    }
}
