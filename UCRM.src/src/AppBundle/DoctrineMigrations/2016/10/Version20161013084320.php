<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161013084320 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE option ALTER option_id SET DEFAULT nextval(\'option_option_id_seq\')');
        $this->addSql('ALTER TABLE setting_category ALTER category_id SET DEFAULT nextval(\'setting_category_category_id_seq\')');
        $this->addSql('ALTER TABLE app_key ALTER key_id SET DEFAULT nextval(\'app_key_key_id_seq\')');
        $this->addSql('ALTER TABLE client ALTER client_id SET DEFAULT nextval(\'client_client_id_seq\')');
        $this->addSql('ALTER TABLE client_bank_account ALTER client_bank_account_id SET DEFAULT nextval(\'client_bank_account_client_bank_account_id_seq\')');
        $this->addSql('ALTER TABLE client_log ALTER log_id SET DEFAULT nextval(\'client_log_log_id_seq\')');
        $this->addSql('ALTER TABLE country ALTER country_id SET DEFAULT nextval(\'country_country_id_seq\')');
        $this->addSql('ALTER TABLE credit ALTER credit_id SET DEFAULT nextval(\'credit_credit_id_seq\')');
        $this->addSql('ALTER TABLE currency ALTER currency_id SET DEFAULT nextval(\'currency_currency_id_seq\')');
        $this->addSql('ALTER TABLE device ALTER device_id SET DEFAULT nextval(\'device_device_id_seq\')');
        $this->addSql('ALTER TABLE device_interface ALTER interface_id SET DEFAULT nextval(\'device_interface_interface_id_seq\')');
        $this->addSql('ALTER TABLE device_interface_ip ALTER ip_id SET DEFAULT nextval(\'device_interface_ip_ip_id_seq\')');
        $this->addSql('ALTER TABLE device_interface_link ALTER link_id SET DEFAULT nextval(\'device_interface_link_link_id_seq\')');
        $this->addSql('ALTER TABLE device_log ALTER log_id SET DEFAULT nextval(\'device_log_log_id_seq\')');
        $this->addSql('ALTER TABLE device_outage ALTER device_outage_id SET DEFAULT nextval(\'device_outage_device_outage_id_seq\')');
        $this->addSql('ALTER TABLE email_log ALTER log_id SET DEFAULT nextval(\'email_log_log_id_seq\')');
        $this->addSql('ALTER TABLE entity_log ALTER log_id SET DEFAULT nextval(\'entity_log_log_id_seq\')');
        $this->addSql('ALTER TABLE fee ALTER fee_id SET DEFAULT nextval(\'fee_fee_id_seq\')');
        $this->addSql('ALTER TABLE general ALTER general_id SET DEFAULT nextval(\'general_general_id_seq\')');
        $this->addSql('ALTER TABLE invoice ALTER invoice_id SET DEFAULT nextval(\'invoice_invoice_id_seq\')');
        $this->addSql('ALTER TABLE invoice_item ALTER item_id SET DEFAULT nextval(\'invoice_item_item_id_seq\')');
        $this->addSql('ALTER TABLE notification_template ALTER template_id SET DEFAULT nextval(\'notification_template_template_id_seq\')');
        $this->addSql('ALTER TABLE organization ALTER organization_id SET DEFAULT nextval(\'organization_organization_id_seq\')');
        $this->addSql('ALTER TABLE organization_bank_account ALTER account_id SET DEFAULT nextval(\'organization_bank_account_account_id_seq\')');
        $this->addSql('ALTER TABLE payment ALTER payment_id SET DEFAULT nextval(\'payment_payment_id_seq\')');
        $this->addSql('ALTER TABLE payment_anet ALTER payment_anet_id SET DEFAULT nextval(\'payment_anet_payment_anet_id_seq\')');
        $this->addSql('ALTER TABLE payment_cover ALTER cover_id SET DEFAULT nextval(\'payment_cover_cover_id_seq\')');
        $this->addSql('ALTER TABLE payment_paypal ALTER payment_paypal_id SET DEFAULT nextval(\'payment_paypal_payment_paypal_id_seq\')');
        $this->addSql('ALTER TABLE payment_plan ALTER payment_plan_id SET DEFAULT nextval(\'payment_plan_payment_plan_id_seq\')');
        $this->addSql('ALTER TABLE payment_stripe ALTER payment_stripe_id SET DEFAULT nextval(\'payment_stripe_payment_stripe_id_seq\')');
        $this->addSql('ALTER TABLE ping_long_term ALTER ping_id SET DEFAULT nextval(\'ping_long_term_ping_id_seq\')');
        $this->addSql('ALTER TABLE ping_service_long_term ALTER ping_id SET DEFAULT nextval(\'ping_service_long_term_ping_id_seq\')');
        $this->addSql('ALTER TABLE ping_service_short_term ALTER ping_id SET DEFAULT nextval(\'ping_service_short_term_ping_id_seq\')');
        $this->addSql('ALTER TABLE ping_short_term ALTER ping_id SET DEFAULT nextval(\'ping_short_term_ping_id_seq\')');
        $this->addSql('ALTER TABLE product ALTER product_id SET DEFAULT nextval(\'product_product_id_seq\')');
        $this->addSql('ALTER TABLE refund ALTER refund_id SET DEFAULT nextval(\'refund_refund_id_seq\')');
        $this->addSql('ALTER TABLE service ALTER service_id SET DEFAULT nextval(\'service_service_id_seq\')');
        $this->addSql('ALTER TABLE service_device ALTER service_device_id SET DEFAULT nextval(\'service_device_service_device_id_seq\')');
        $this->addSql('ALTER TABLE service_device_log ALTER log_id SET DEFAULT nextval(\'service_device_log_log_id_seq\')');
        $this->addSql('ALTER TABLE service_device_outage ALTER service_device_outage_id SET DEFAULT nextval(\'service_device_outage_service_device_outage_id_seq\')');
        $this->addSql('ALTER TABLE service_ip ALTER ip_id SET DEFAULT nextval(\'service_ip_ip_id_seq\')');
        $this->addSql('ALTER TABLE service_stop_reason ALTER reason_id SET DEFAULT nextval(\'service_stop_reason_reason_id_seq\')');
        $this->addSql('ALTER TABLE service_surcharge ALTER service_surcharge_id SET DEFAULT nextval(\'service_surcharge_service_surcharge_id_seq\')');
        $this->addSql('ALTER TABLE site ALTER site_id SET DEFAULT nextval(\'site_site_id_seq\')');
        $this->addSql('ALTER TABLE state ALTER state_id SET DEFAULT nextval(\'state_state_id_seq\')');
        $this->addSql('ALTER TABLE surcharge ALTER surcharge_id SET DEFAULT nextval(\'surcharge_surcharge_id_seq\')');
        $this->addSql('ALTER TABLE tariff ALTER tariff_id SET DEFAULT nextval(\'tariff_tariff_id_seq\')');
        $this->addSql('ALTER TABLE tariff_period ALTER period_id SET DEFAULT nextval(\'tariff_period_period_id_seq\')');
        $this->addSql('ALTER TABLE tax ALTER tax_id SET DEFAULT nextval(\'tax_tax_id_seq\')');
        $this->addSql('ALTER TABLE "user" ALTER user_id SET DEFAULT nextval(\'user_user_id_seq\')');
        $this->addSql('ALTER TABLE user_group ALTER group_id SET DEFAULT nextval(\'user_group_group_id_seq\')');
        $this->addSql('ALTER TABLE user_group_permission ALTER permission_id SET DEFAULT nextval(\'user_group_permission_permission_id_seq\')');
        $this->addSql('ALTER TABLE user_group_special_permission ALTER special_permission_id SET DEFAULT nextval(\'user_group_special_permission_special_permission_id_seq\')');
        $this->addSql('ALTER TABLE vendor ALTER vendor_id SET DEFAULT nextval(\'vendor_vendor_id_seq\')');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE notification_template ALTER template_id DROP DEFAULT');
        $this->addSql('ALTER TABLE site ALTER site_id DROP DEFAULT');
        $this->addSql('ALTER TABLE vendor ALTER vendor_id DROP DEFAULT');
        $this->addSql('ALTER TABLE client_bank_account ALTER client_bank_account_id DROP DEFAULT');
        $this->addSql('ALTER TABLE credit ALTER credit_id DROP DEFAULT');
        $this->addSql('ALTER TABLE device_interface_link ALTER link_id DROP DEFAULT');
        $this->addSql('ALTER TABLE device_interface_ip ALTER ip_id DROP DEFAULT');
        $this->addSql('ALTER TABLE device ALTER device_id DROP DEFAULT');
        $this->addSql('ALTER TABLE product ALTER product_id DROP DEFAULT');
        $this->addSql('ALTER TABLE fee ALTER fee_id DROP DEFAULT');
        $this->addSql('ALTER TABLE currency ALTER currency_id DROP DEFAULT');
        $this->addSql('ALTER TABLE option ALTER option_id DROP DEFAULT');
        $this->addSql('ALTER TABLE invoice_item ALTER item_id DROP DEFAULT');
        $this->addSql('ALTER TABLE setting_category ALTER category_id DROP DEFAULT');
        $this->addSql('ALTER TABLE organization_bank_account ALTER account_id DROP DEFAULT');
        $this->addSql('ALTER TABLE payment_stripe ALTER payment_stripe_id DROP DEFAULT');
        $this->addSql('ALTER TABLE payment ALTER payment_id DROP DEFAULT');
        $this->addSql('ALTER TABLE device_interface ALTER interface_id DROP DEFAULT');
        $this->addSql('ALTER TABLE invoice ALTER invoice_id DROP DEFAULT');
        $this->addSql('ALTER TABLE payment_cover ALTER cover_id DROP DEFAULT');
        $this->addSql('ALTER TABLE payment_paypal ALTER payment_paypal_id DROP DEFAULT');
        $this->addSql('ALTER TABLE client ALTER client_id DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER user_id DROP DEFAULT');
        $this->addSql('ALTER TABLE surcharge ALTER surcharge_id DROP DEFAULT');
        $this->addSql('ALTER TABLE tariff ALTER tariff_id DROP DEFAULT');
        $this->addSql('ALTER TABLE service_ip ALTER ip_id DROP DEFAULT');
        $this->addSql('ALTER TABLE service_stop_reason ALTER reason_id DROP DEFAULT');
        $this->addSql('ALTER TABLE user_group ALTER group_id DROP DEFAULT');
        $this->addSql('ALTER TABLE service_surcharge ALTER service_surcharge_id DROP DEFAULT');
        $this->addSql('ALTER TABLE organization ALTER organization_id DROP DEFAULT');
        $this->addSql('ALTER TABLE service ALTER service_id DROP DEFAULT');
        $this->addSql('ALTER TABLE tax ALTER tax_id DROP DEFAULT');
        $this->addSql('ALTER TABLE country ALTER country_id DROP DEFAULT');
        $this->addSql('ALTER TABLE state ALTER state_id DROP DEFAULT');
        $this->addSql('ALTER TABLE tariff_period ALTER period_id DROP DEFAULT');
        $this->addSql('ALTER TABLE user_group_permission ALTER permission_id DROP DEFAULT');
        $this->addSql('ALTER TABLE device_log ALTER log_id DROP DEFAULT');
        $this->addSql('ALTER TABLE general ALTER general_id DROP DEFAULT');
        $this->addSql('ALTER TABLE email_log ALTER log_id DROP DEFAULT');
        $this->addSql('ALTER TABLE entity_log ALTER log_id DROP DEFAULT');
        $this->addSql('ALTER TABLE payment_plan ALTER payment_plan_id DROP DEFAULT');
        $this->addSql('ALTER TABLE payment_anet ALTER payment_anet_id DROP DEFAULT');
        $this->addSql('ALTER TABLE refund ALTER refund_id DROP DEFAULT');
        $this->addSql('ALTER TABLE client_log ALTER log_id DROP DEFAULT');
        $this->addSql('ALTER TABLE user_group_special_permission ALTER special_permission_id DROP DEFAULT');
        $this->addSql('ALTER TABLE service_device ALTER service_device_id DROP DEFAULT');
        $this->addSql('ALTER TABLE device_outage ALTER device_outage_id DROP DEFAULT');
        $this->addSql('ALTER TABLE service_device_outage ALTER service_device_outage_id DROP DEFAULT');
        $this->addSql('ALTER TABLE service_device_log ALTER log_id DROP DEFAULT');
        $this->addSql('ALTER TABLE app_key ALTER key_id DROP DEFAULT');
        $this->addSql('ALTER TABLE ping_long_term ALTER ping_id DROP DEFAULT');
        $this->addSql('ALTER TABLE ping_service_long_term ALTER ping_id DROP DEFAULT');
        $this->addSql('ALTER TABLE ping_service_short_term ALTER ping_id DROP DEFAULT');
        $this->addSql('ALTER TABLE ping_short_term ALTER ping_id DROP DEFAULT');
    }
}
