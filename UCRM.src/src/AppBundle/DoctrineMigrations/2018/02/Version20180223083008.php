<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180223083008 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['es', 'es_ES']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['tr', 'tr_TR']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['nl', 'nl_NL']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['lv', 'lv_LV']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['sv', 'sv_SE']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['de', 'de_DE']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['hu', 'hu_HU']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['da', 'da_DK']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['sk', 'sk_SK']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['pt', 'pt_PT']);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['es_ES', 'es']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['tr_TR', 'tr']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['nl_NL', 'nl']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['lv_LV', 'lv']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['sv_SE', 'sv']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['de_DE', 'de']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['hu_HU', 'hu']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['da_DK', 'da']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['sk_SK', 'sk']);
        $this->addSql('UPDATE option SET value = ? WHERE code = \'APP_LOCALE\' AND value = ?', ['pt_PT', 'pt']);
    }
}
