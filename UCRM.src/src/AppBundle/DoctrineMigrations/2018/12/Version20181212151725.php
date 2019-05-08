<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181212151725 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'UPDATE notification_template SET body = :new WHERE body = :old;',
            [
                'new' => $this->getNewTemplate(),
                'old' => $this->getOldTemplate(),
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'UPDATE notification_template SET body = :old WHERE body = :new;',
            [
                'new' => $this->getNewTemplate(),
                'old' => $this->getOldTemplate(),
            ]
        );
    }

    private function getNewTemplate(): string
    {
        return '<p>Dear %CLIENT_NAME%,<br> Your UCRM account has just been created.<br>Your username is <b>%CLIENT_USERNAME%</b> and you can create your password at <a href="%CLIENT_FIRST_LOGIN_URL%">%CLIENT_FIRST_LOGIN_URL%</a></p>';
    }

    private function getOldTemplate(): string
    {
        return '<p>Dear %CLIENT_NAME%! Your UCRM account has just been created. You can log in here: <a href="%CLIENT_FIRST_LOGIN_URL%">%CLIENT_FIRST_LOGIN_URL%</a></p>';
    }
}
