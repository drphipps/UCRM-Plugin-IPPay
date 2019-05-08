<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170306133609 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            sprintf(
                'UPDATE notification_template SET body = \'%s\' WHERE body = \'%s\';',
                '<p>Dear %CLIENT_NAME%! To reset your password, please continue by clicking here: <a href="%CLIENT_RESET_PASSWORD_URL%">%CLIENT_RESET_PASSWORD_URL%</a></p>',
                '<p>Dear %CLIENT_NAME%! To reset your password, please continue by clicking here: %CLIENT_RESET_PASSWORD_URL%</p>'
            )
        );
        $this->addSql(
            sprintf(
                'UPDATE notification_template SET body = \'%s\' WHERE body = \'%s\';',
                '<p>Dear %CLIENT_NAME%! Your UCRM account has just been created. You can log in here: <a href="%CLIENT_FIRST_LOGIN_URL%">%CLIENT_FIRST_LOGIN_URL%</a></p>',
                '<p>Dear %CLIENT_NAME%! Your UCRM account has just been created. You can log in here: %CLIENT_FIRST_LOGIN_URL%</p>'
            )
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            sprintf(
                'UPDATE notification_template SET body = \'%s\' WHERE body = \'%s\';',
                '<p>Dear %CLIENT_NAME%! To reset your password, please continue by clicking here: %CLIENT_RESET_PASSWORD_URL%</p>',
                '<p>Dear %CLIENT_NAME%! To reset your password, please continue by clicking here: <a href="%CLIENT_RESET_PASSWORD_URL%">%CLIENT_RESET_PASSWORD_URL%</a></p>'
            )
        );
        $this->addSql(
            sprintf(
                'UPDATE notification_template SET body = \'%s\' WHERE body = \'%s\';',
                '<p>Dear %CLIENT_NAME%! Your UCRM account has just been created. You can log in here: %CLIENT_FIRST_LOGIN_URL%</p>',
                '<p>Dear %CLIENT_NAME%! Your UCRM account has just been created. You can log in here: <a href="%CLIENT_FIRST_LOGIN_URL%">%CLIENT_FIRST_LOGIN_URL%</a></p>'
            )
        );
    }
}
