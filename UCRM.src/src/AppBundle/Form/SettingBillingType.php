<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Form\Data\Settings\BillingData;
use AppBundle\Util\Invoicing;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingBillingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'invoiceTimeHour',
            IntegerType::class,
            [
                'label' => Option::NAMES[Option::INVOICE_TIME_HOUR],
                'attr' => [
                    'min' => 0,
                    'max' => 23,
                ],
            ]
        );

        $builder->add(
            'billingCycleType',
            ChoiceType::class,
            [
                'label' => Option::NAMES[Option::BILLING_CYCLE_TYPE],
                'choices' => array_flip(Invoicing::BILLING_CYCLES),
            ]
        );

        $builder->add(
            'invoicingPeriodType',
            ChoiceType::class,
            [
                'label' => Option::NAMES[Option::INVOICING_PERIOD_TYPE],
                'choices' => array_flip(Service::INVOICING_PERIOD_TYPE),
            ]
        );

        $builder->add(
            'invoicePeriodStartDay',
            InvoicePeriodStartDayType::class,
            [
                'label' => Option::NAMES[Option::INVOICE_PERIOD_START_DAY],
                'invoice_period_start_day_choices' => array_merge(
                    [
                        Option::INVOICE_PERIOD_START_DAY_TODAY => 'today',
                    ],
                    Service::INVOICING_PERIOD_START_DAY
                ),
            ]
        );

        $builder->add(
            'serviceInvoicingDayAdjustment',
            IntegerType::class,
            [
                'label' => Option::NAMES[Option::SERVICE_INVOICING_DAY_ADJUSTMENT],
                'attr' => [
                    'min' => 0,
                ],
            ]
        );

        $builder->add(
            'discountInvoiceLabel',
            TextType::class,
            [
                'label' => Option::NAMES[Option::DISCOUNT_INVOICE_LABEL],
                'required' => false,
            ]
        );

        if ($options['allow_invoice_item_rounding']) {
            $builder->add(
                'invoiceItemRounding',
                ChoiceType::class,
                [
                    'label' => Option::NAMES[Option::INVOICE_ITEM_ROUNDING],
                    'choices' => array_flip(FinancialInterface::ITEM_ROUNDINGS),
                ]
            );
        }

        $builder->add(
            'pricingMode',
            ChoiceType::class,
            [
                'label' => Option::NAMES[Option::PRICING_MODE],
                'choices' => array_flip(Option::PRICING_MODES),
                'attr' => [
                    'data-pricing-mode-without-taxes' => Option::PRICING_MODE_WITHOUT_TAXES,
                    'data-pricing-mode-with-taxes' => Option::PRICING_MODE_WITH_TAXES,
                ],
            ]
        );

        $builder->add(
            'pricingTaxCoefficientPrecision',
            IntegerType::class,
            [
                'label' => Option::NAMES[Option::PRICING_TAX_COEFFICIENT_PRECISION],
                'required' => false,
            ]
        );

        $builder->add(
            'sendInvoiceByEmail',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::SEND_INVOICE_BY_EMAIL],
                'required' => false,
            ]
        );

        $builder->add(
            'sendInvoiceByPost',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::SEND_INVOICE_BY_POST],
                'required' => false,
            ]
        );

        $builder->add(
            'stopInvoicing',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::STOP_INVOICING],
                'required' => false,
            ]
        );

        $builder->add(
            'subscriptionsEnabledCustom',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::SUBSCRIPTIONS_ENABLED_CUSTOM],
                'required' => false,
            ]
        );

        $builder->add(
            'subscriptionsEnabledLinked',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::SUBSCRIPTIONS_ENABLED_LINKED],
                'required' => false,
            ]
        );

        $builder->add(
            'generateProformaInvoices',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::GENERATE_PROFORMA_INVOICES],
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => BillingData::class,
                'allow_invoice_item_rounding' => false,
            ]
        );

        $resolver->setAllowedTypes('allow_invoice_item_rounding', 'bool');
    }
}
