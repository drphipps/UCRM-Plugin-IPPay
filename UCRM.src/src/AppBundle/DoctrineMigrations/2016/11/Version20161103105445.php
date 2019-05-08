<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161103105445 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql(
            sprintf(
                'INSERT INTO notification_template (template_id, subject, body, type) VALUES (\'14\', \'SUSPEND TERMINATED\', \'%s\', \'14\');',
                "<p>Dear %CLIENT_NAME%,<br />\nyour internet service %SERVICE_TARIFF% has been terminated.</p>"
            )
        );
        $this->addSql('UPDATE notification_template SET body = \'<p>Dear %CLIENT_NAME%! To reset your password, please continue by clicking here: %CLIENT_RESET_PASSWORD_URL%</p>\' WHERE body = \'Dear %CLIENT_FIRST_NAME%! To reset your password, please continue by clicking here: %CLIENT_RESET_PASSWORD_URL%\' OR body = \'<p>Dear %CLIENT_FIRST_NAME%! To reset your password, please continue by clicking here: %CLIENT_RESET_PASSWORD_URL%</p>\';');
        $this->addSql('UPDATE notification_template SET body = \'<p>Dear %CLIENT_NAME%! Your UCRM account has just been created. You can log in here: %CLIENT_FIRST_LOGIN_URL%</p>\' WHERE body = \'Dear %CLIENT_FIRST_NAME%! Your UCRM account has just been created. You can log in here: %CLIENT_FIRST_LOGIN_URL%\' OR body = \'<p>Dear %CLIENT_FIRST_NAME%! Your UCRM account has just been created. You can log in here: %CLIENT_FIRST_LOGIN_URL%</p>\';');
        $this->addSql('UPDATE notification_template SET body = \'<p>Dear %CLIENT_NAME%! We are sending you new invoice for internet services.</p>\' WHERE body = \'Dear %CLIENT_FIRST_NAME%! We are sending you new invoice for internet services.\' OR body = \'<p>Dear %CLIENT_FIRST_NAME%! We are sending you new invoice for internet services.</p>\';');
        $this->addSql('UPDATE notification_template SET body = \'<p>Dear %CLIENT_NAME%! Please help us to identify your payment for invoice %INVOICE_NUMBER%.</p>\' WHERE body = \'Dear %CLIENT_FIRST_NAME%! Please help us to identify your payment for invoice %INVOICE_NUMBER%\' OR body = \'<p>Dear %CLIENT_FIRST_NAME%! Please help us to identify your payment for invoice %INVOICE_NUMBER%</p>\';');
        $this->addSql('UPDATE notification_template SET body = \'<p>Dear %CLIENT_NAME%! We have received your payment, you can find the receipt below.</p>\' WHERE body = \'<p>Dear %CLIENT_FIRST_NAME%! We have received your payment, you can find the receipt below.</p>\';');
        $this->addSql('UPDATE notification_template SET body = \'<p>Dear %CLIENT_NAME%! Your service suspension has been postponed.</p>\' WHERE body = \'Dear %CLIENT_FIRST_NAME%! Your service suspension has been postponed\' OR body = \'<p>Dear %CLIENT_FIRST_NAME%! Your service suspension has been postponed</p>\';');
        $this->addSql('UPDATE notification_template SET body = \'<p>Dear %CLIENT_NAME%! As we did not receive any payment for this service, it will be temporarily suspended.</p>\' WHERE body = \'Dear %CLIENT_FIRST_NAME%! As we did not receive any payment for this service, it will be temporarily suspended\' OR body = \'<p>Dear %CLIENT_FIRST_NAME%! As we did not receive any payment for this service, it will be temporarily suspended</p>\';');
        $this->addSql('UPDATE notification_template SET body = \'<p>Dear %CLIENT_NAME%! Your Authorize.Net subscription has been cancelled.</p>\' WHERE body = \'<p>Dear %CLIENT_FIRST_NAME%! Your Authorize.Net subscription has been cancelled.</p>\';');
        $this->addSql('UPDATE notification_template SET body = \'<p>Dear %CLIENT_NAME%! Your PayPal subscription has been cancelled.</p>\' WHERE body = \'<p>Dear %CLIENT_FIRST_NAME%! Your PayPal subscription has been cancelled.</p>\';');
        $this->addSql('UPDATE notification_template SET body = \'<p>Dear %CLIENT_NAME%! Your Stripe subscription has been cancelled.</p>\' WHERE body = \'Dear %CLIENT_FIRST_NAME%! Your stripe subscription has been cancelled.\' OR  body = \'<p>Dear %CLIENT_FIRST_NAME%! Your stripe subscription has been cancelled.</p>\';');
        $this->addSql(
            sprintf(
                'UPDATE notification_template SET body = \'%s\' WHERE body = \'%s\' OR body LIKE \'%s\' OR body LIKE \'%s\';',
                "<p>Dear %CLIENT_NAME%,<br />\nyour internet service %SERVICE_TARIFF% has been temporarily suspended as we did not receive any payment and your invoice is overdue.<br />\nPlease follow the instructions in your email to pay for the invoice.</p>",
                '<p>Dear %CLIENT_FIRST_NAME% %CLIENT_LAST_NAME%,<br />your internet service %SERVICE_TARIFF% has been temporarily suspended as we did not receive any payment and your invoice is overdue.<br />Please follow the instructions in your email to pay for the invoice.</p>',
                '<p>Dear %CLIENT_FIRST_NAME% %CLIENT_LAST_NAME%,<br />_your internet service %SERVICE_TARIFF% has been temporarily suspended as we did not receive any payment and your invoice is overdue.<br />_Please follow the instructions in your email to pay for the invoice.</p>',
                '<p>Dear %CLIENT_FIRST_NAME% %CLIENT_LAST_NAME%,<br />__your internet service %SERVICE_TARIFF% has been temporarily suspended as we did not receive any payment and your invoice is overdue.<br />__Please follow the instructions in your email to pay for the invoice.</p>'
            )
        );

        $this->addSql('UPDATE service SET status = NULL WHERE status = 4;');

        // The "OR status IS NULL" part is really needed.
        $this->addSql('UPDATE service SET status = 0 WHERE active_from > NOW()::date AND (status <> 0 OR status IS NULL);');
        $this->addSql('UPDATE service SET status = 2 WHERE active_to < NOW()::date AND (status <> 2 OR status IS NULL);');
        $this->addSql('UPDATE service SET status = 1 WHERE active_from <= NOW()::date AND (active_to >= NOW()::date OR active_to IS NULL) AND reason_id IS NULL AND (status <> 1 OR status IS NULL);');
        $this->addSql('UPDATE service SET status = 3 WHERE active_from <= NOW()::date AND (active_to >= NOW()::date OR active_to IS NULL) AND reason_id IS NOT NULL AND (status <> 3 OR status IS NULL);');

        $this->addSql('ALTER TABLE service ALTER status TYPE SMALLINT');
        $this->addSql('ALTER TABLE service ALTER status DROP DEFAULT');
        $this->addSql('ALTER TABLE service ALTER status SET NOT NULL');
    }

    public function down(Schema $schema)
    {
        $this->addSql('DELETE FROM notification_template WHERE template_id = 14');

        $this->addSql('ALTER TABLE service ALTER status TYPE INT');
        $this->addSql('ALTER TABLE service ALTER status DROP DEFAULT');
        $this->addSql('ALTER TABLE service ALTER status DROP NOT NULL');
    }
}
