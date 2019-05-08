<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Twig;

use AppBundle\Service\Financial\TemplateData;

class SandboxSecurityPolicyFactory
{
    public static function create(): \Twig_Sandbox_SecurityPolicy
    {
        $securityPolicy = new \Twig_Sandbox_SecurityPolicy();
        $securityPolicy->setAllowedTags(
            [
                'if',
                'trans',
                'transChoice',
                'for',
                'set',
            ]
        );
        $securityPolicy->setAllowedFilters(
            [
                'trans',
                'date',
                'date_modify',
                'escape',
                'nl2br',
                'round',
                'number_format',
                'lower',
                'upper',
                'capitalize',
                'title',
                'abs',
                'format',
            ]
        );
        $securityPolicy->setAllowedProperties(
            [
                TemplateData\Client::class => array_keys(get_object_vars(new TemplateData\Client())),
                TemplateData\Organization::class => array_keys(get_object_vars(new TemplateData\Organization())),
                TemplateData\Invoice::class => array_keys(get_object_vars(new TemplateData\Invoice())),
                TemplateData\Quote::class => array_keys(get_object_vars(new TemplateData\Quote())),
                TemplateData\FinancialItem::class => array_keys(get_object_vars(new TemplateData\FinancialItem())),
                TemplateData\InvoiceTotals::class => array_keys(get_object_vars(new TemplateData\InvoiceTotals())),
                TemplateData\QuoteTotals::class => array_keys(get_object_vars(new TemplateData\QuoteTotals())),
                TemplateData\TaxTotal::class => array_keys(get_object_vars(new TemplateData\TaxTotal())),
                TemplateData\Payment::class => array_keys(get_object_vars(new TemplateData\Payment())),
                TemplateData\PaymentCover::class => array_keys(get_object_vars(new TemplateData\PaymentCover())),
                TemplateData\ClientContact::class => array_keys(get_object_vars(new TemplateData\ClientContact())),
                TemplateData\ContactType::class => array_keys(get_object_vars(new TemplateData\ContactType())),
                TemplateData\PaymentDetail::class => array_keys(get_object_vars(new TemplateData\PaymentDetail())),
                TemplateData\TaxRecapitulation::class => array_keys(get_object_vars(new TemplateData\TaxRecapitulation())),
                TemplateData\Refund::class => array_keys(get_object_vars(new TemplateData\Refund())),
                TemplateData\AccountStatement::class => array_keys(get_object_vars(new TemplateData\AccountStatement())),
                TemplateData\AccountStatementItem::class => array_keys(get_object_vars(new TemplateData\AccountStatementItem())),
            ]
        );
        $securityPolicy->setAllowedMethods(
            [
                TemplateData\Client::class => ['getAttribute'],
                TemplateData\Invoice::class => ['getAttribute'],
                TemplateData\PaymentCover::class => ['getInvoiceAttribute'],
            ]
        );

        return $securityPolicy;
    }
}
