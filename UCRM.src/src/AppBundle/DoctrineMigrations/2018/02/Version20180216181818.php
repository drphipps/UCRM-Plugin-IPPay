<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180216181818 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['es', 'es_ES']);
        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['tr', 'tr_TR']);
        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['nl', 'nl_NL']);
        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['lv', 'lv_LV']);
        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['sv', 'sv_SE']);
        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['de', 'de_DE']);
        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['hu', 'hu_HU']);
        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['da', 'da_DK']);
        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['sk', 'sk_SK']);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['es_ES', 'es']);
        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['tr_TR', 'tr']);
        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['nl_NL', 'nl']);
        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['lv_LV', 'lv']);
        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['sv_SE', 'sv']);
        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['de_DE', 'de']);
        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['hu_HU', 'hu']);
        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['da_DK', 'da']);
        $this->addSql('UPDATE locale SET code = ? WHERE code = ?', ['sk_SK', 'sk']);
    }
}
