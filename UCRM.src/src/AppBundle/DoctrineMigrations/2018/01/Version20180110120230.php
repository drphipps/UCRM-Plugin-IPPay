<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180110120230 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket_imap_inbox DROP CONSTRAINT FK_40057D637B3D25C6');
        $this->addSql('ALTER TABLE ticket_imap_inbox ADD CONSTRAINT FK_40057D637B3D25C6 FOREIGN KEY (ticket_group_id) REFERENCES ticket_group (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ticket_imap_inbox DROP CONSTRAINT fk_40057d637b3d25c6');
        $this->addSql('ALTER TABLE ticket_imap_inbox ADD CONSTRAINT fk_40057d637b3d25c6 FOREIGN KEY (ticket_group_id) REFERENCES ticket_group (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
