<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171101093550 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            'INSERT INTO notification_template (template_id, subject, body, type)
            VALUES (
                27,
                \'Subscription cancelled\',
                (
                    SELECT COALESCE(
                        (
                            SELECT body
                            FROM notification_template
                            WHERE template_id IN (10, 12, 13, 18, 23)
                            AND body NOT IN (
                             \'<p>Dear %CLIENT_NAME%! Your Stripe subscription has been cancelled.</p>\',
                             \'<p>Dear %CLIENT_NAME%! Your PayPal subscription has been cancelled.</p>\',
                             \'<p>Dear %CLIENT_NAME%! Your Authorize.Net subscription has been cancelled.</p>\',
                             \'<p>Dear %CLIENT_NAME%! Your IPPay subscription has been cancelled.</p>\',
                             \'<p>Dear %CLIENT_NAME%! Your MercadoPago subscription has been cancelled.</p>\'
                            )
                            LIMIT 1
                        ),
                        \'<p>Dear %CLIENT_NAME%! Your %PAYMENT_PLAN_PROVIDER% subscription has been cancelled.</p>\'
                    )
                ),
                27
            )');

        $this->addSql('DELETE FROM notification_template WHERE template_id IN (10, 12, 13, 18, 23)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('INSERT INTO notification_template (template_id, subject, body, type) VALUES (10, \'Stripe subscription cancelled\', \'<p>Dear %CLIENT_NAME%! Your Stripe subscription has been cancelled.</p>\', 10)');
        $this->addSql('INSERT INTO notification_template (template_id, subject, body, type) VALUES (12, \'PayPal subscription cancelled\', \'<p>Dear %CLIENT_NAME%! Your PayPal subscription has been cancelled.</p>\', 12)');
        $this->addSql('INSERT INTO notification_template (template_id, subject, body, type) VALUES (13, \'Authorize.Net subscription cancelled\', \'<p>Dear %CLIENT_NAME%! Your Authorize.Net subscription has been cancelled.</p>\', 13)');
        $this->addSql('INSERT INTO notification_template (template_id, subject, body, type) VALUES (18, \'IPPay subscription cancelled\', \'<p>Dear %CLIENT_NAME%! Your IPPay subscription has been cancelled.</p>\', 18)');
        $this->addSql('INSERT INTO notification_template (template_id, subject, body, type) VALUES (23, \'MercadoPago subscription cancelled\', \'<p>Dear %CLIENT_NAME%! Your MercadoPago subscription has been cancelled.</p>\', 23)');
    }
}
