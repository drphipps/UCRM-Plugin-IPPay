<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181109115404 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE header_notification_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE header_notification_status_id_seq CASCADE');

        // migrate header_notification_status IDs to UUIDs
        $this->addSql('ALTER TABLE header_notification_status ADD uuid UUID DEFAULT NULL');
        $this->addSql('UPDATE header_notification_status SET uuid = uuid_generate_v4()');
        $this->addSql('ALTER TABLE header_notification_status ALTER uuid SET NOT NULL');
        $this->addSql('ALTER TABLE header_notification_status DROP CONSTRAINT header_notification_status_pkey');
        $this->addSql('ALTER TABLE header_notification_status DROP id');
        $this->addSql('ALTER TABLE header_notification_status RENAME COLUMN uuid TO id');
        $this->addSql('ALTER TABLE header_notification_status ADD PRIMARY KEY (id)');

        // migrate header_notification_status <=> header_notification
        $this->addSql('ALTER TABLE header_notification_status ADD header_notification_uuid UUID DEFAULT NULL');
        $this->addSql(
            '
              UPDATE header_notification_status
              SET header_notification_uuid = hn.uuid
              FROM header_notification hn
              WHERE hn.id = header_notification_status.header_notification_id
            '
        );
        $this->addSql('ALTER TABLE header_notification_status ALTER header_notification_uuid SET NOT NULL');
        $this->addSql('DROP INDEX IDX_B3FC8A5FB8F55353');
        $this->addSql('ALTER TABLE header_notification_status DROP CONSTRAINT FK_B3FC8A5FB8F55353');
        $this->addSql('ALTER TABLE header_notification_status DROP header_notification_id');
        $this->addSql('ALTER TABLE header_notification_status RENAME COLUMN header_notification_uuid TO header_notification_id');

        // migrate header_notification IDs to UUIDs
        $this->addSql('ALTER TABLE header_notification DROP CONSTRAINT header_notification_pkey');
        $this->addSql('ALTER TABLE header_notification DROP id');
        $this->addSql('DROP INDEX UNIQ_32E4644DD17F50A6');
        $this->addSql('ALTER TABLE header_notification RENAME COLUMN uuid TO id');
        $this->addSql('ALTER TABLE header_notification ADD PRIMARY KEY (id)');

        // create header_notification_status <=> header_notification index and foreign key
        $this->addSql('CREATE INDEX IDX_B3FC8A5FB8F55353 ON header_notification_status (header_notification_id)');
        $this->addSql('ALTER TABLE header_notification_status ADD CONSTRAINT FK_B3FC8A5FB8F55353 FOREIGN KEY (header_notification_id) REFERENCES header_notification (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        throw new \RuntimeException('This migration cannot be down-migrated.');
    }
}
