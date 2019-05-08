<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170202153710 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE INDEX IDX_54C9B0DF4AF38FD1 ON surcharge (deleted_at)');
        $this->addSql('CREATE INDEX IDX_8E81BA764AF38FD1 ON tax (deleted_at)');
        $this->addSql('CREATE INDEX IDX_E19D9AD24AF38FD1 ON service (deleted_at)');
        $this->addSql('CREATE INDEX IDX_92FB68E4AF38FD1 ON device (deleted_at)');
        $this->addSql('CREATE INDEX IDX_9465207D4AF38FD1 ON tariff (deleted_at)');
        $this->addSql('CREATE INDEX IDX_694309E44AF38FD1 ON site (deleted_at)');
        $this->addSql('CREATE INDEX IDX_D34A04AD4AF38FD1 ON product (deleted_at)');
        $this->addSql('CREATE INDEX IDX_8D93D6494AF38FD1 ON "user" (deleted_at)');
        $this->addSql('CREATE INDEX IDX_F14F84C4AF38FD1 ON device_interface (deleted_at)');
        $this->addSql('CREATE INDEX IDX_C74404554AF38FD1 ON client (deleted_at)');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX IDX_694309E44AF38FD1');
        $this->addSql('DROP INDEX IDX_92FB68E4AF38FD1');
        $this->addSql('DROP INDEX IDX_D34A04AD4AF38FD1');
        $this->addSql('DROP INDEX IDX_F14F84C4AF38FD1');
        $this->addSql('DROP INDEX IDX_C74404554AF38FD1');
        $this->addSql('DROP INDEX IDX_54C9B0DF4AF38FD1');
        $this->addSql('DROP INDEX IDX_8E81BA764AF38FD1');
        $this->addSql('DROP INDEX IDX_9465207D4AF38FD1');
        $this->addSql('DROP INDEX IDX_E19D9AD24AF38FD1');
        $this->addSql('DROP INDEX IDX_8D93D6494AF38FD1');
    }
}
