<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170504101757 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client ADD late_fee_delay_days INT DEFAULT NULL');
        $this->addSql(
            'INSERT INTO option (option_id, code, value) VALUES (?, ?, (SELECT value FROM option WHERE code = ?))',
            [
                88,
                'LATE_FEE_DELAY_DAYS',
                'STOP_SERVICE_DUE_DAYS',
            ]
        );
        $this->addSql('INSERT INTO user_group_permission (permission_id, group_id, module_name, permission) VALUES (46, 1, \'AppBundle\Controller\SettingLateFeeController\', \'edit\')');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE client DROP late_fee_delay_days');
        $this->addSql('DELETE FROM option WHERE option_id = 88');
        $this->addSql('DELETE FROM user_group_permission WHERE permission_id = 46');
    }
}
