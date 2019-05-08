<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180605122646 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO user_group_permission ("group_id", "module_name", "permission")
            SELECT ugsp.group_id, \'AppBundle\\Controller\\ClientImportController\', ugsp.permission
            FROM user_group_permission ugsp
            WHERE module_name = \'AppBundle\\Controller\\ImportController\'');
        $this->addSql('INSERT INTO user_group_permission ("group_id", "module_name", "permission")
            SELECT ugsp.group_id, \'AppBundle\\Controller\\PaymentImportController\', ugsp.permission
            FROM user_group_permission ugsp
            WHERE module_name = \'AppBundle\\Controller\\ImportController\'');
        $this->addSql('DELETE FROM user_group_permission 
            WHERE module_name = \'AppBundle\\Controller\\ImportController\'');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
        $this->addSql('INSERT INTO user_group_permission ("group_id", "module_name", "permission")
            SELECT ugsp.group_id, \'AppBundle\\Controller\\ImportController\', ugsp.permission
            FROM user_group_permission ugsp
            WHERE module_name = \'AppBundle\\Controller\\ClientImportController\'');
        $this->addSql('DELETE FROM user_group_permission 
            WHERE module_name = \'AppBundle\\Controller\\ClientImportController\'');
        $this->addSql('DELETE FROM user_group_permission 
            WHERE module_name = \'AppBundle\\Controller\\PaymentImportController\'');
    }
}
