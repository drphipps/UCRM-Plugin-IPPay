<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180927101801 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_41AE99DA349BE503 ON payment_paypal (paypal_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_81C977CC3F1B1098 ON payment_stripe (stripe_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX UNIQ_81C977CC3F1B1098');
        $this->addSql('DROP INDEX UNIQ_41AE99DA349BE503');
    }
}
