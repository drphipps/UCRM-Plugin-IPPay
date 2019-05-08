<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItemOther;
use AppBundle\Entity\Option;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class InvoiceType extends FinancialType
{
    public const APPLY_CREDIT = 'applyCredit';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        /** @var Invoice $invoice */
        $invoice = $options['data'];

        if ($options['display_apply_credit_toggle']) {
            $builder->add(
                self::APPLY_CREDIT,
                CheckboxType::class,
                [
                    'mapped' => false,
                    'required' => false,
                    'label' => 'Use credit',
                ]
            );
        }

        $builder->add(
            'isProforma',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Proforma invoice',
            ]
        );

        $builder->add(
            'invoiceMaturityDays',
            IntegerType::class,
            [
                'required' => true,
            ]
        );

        $builder->add(
            'invoiceNumber',
            TextType::class,
            [
                'required' => true,
            ]
        );

        $builder->add(
            'financialItemFees',
            CollectionType::class,
            [
                'entry_type' => InvoiceItemFeeType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'mapped' => false,
                'constraints' => new Assert\Valid(),
                'entry_options' => [
                    'multipleTaxes' => $invoice->getPricingMode() === Option::PRICING_MODE_WITHOUT_TAXES,
                ],
            ]
        );

        $invoiceItemOtherPrototype = new InvoiceItemOther();
        $invoiceItemOtherPrototype->setPrice(0.0);
        if ($client = $invoice->getClient()) {
            $invoiceItemOtherPrototype->setTax1($client->getTax1());
            $invoiceItemOtherPrototype->setTax2($client->getTax2());
            $invoiceItemOtherPrototype->setTax3($client->getTax3());
        }
        $builder->add(
            'financialItemOthers',
            CollectionType::class,
            [
                'entry_type' => InvoiceItemOtherType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'mapped' => false,
                'constraints' => new Assert\Valid(),
                'entry_options' => [
                    'multipleTaxes' => $invoice->getPricingMode() === Option::PRICING_MODE_WITHOUT_TAXES,
                ],
                'prototype_data' => $invoiceItemOtherPrototype,
            ]
        );

        $builder->add(
            'financialItemProducts',
            CollectionType::class,
            [
                'entry_type' => InvoiceItemProductType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'mapped' => false,
                'constraints' => new Assert\Valid(),
                'entry_options' => [
                    'multipleTaxes' => $invoice->getPricingMode() === Option::PRICING_MODE_WITHOUT_TAXES,
                ],
            ]
        );

        $builder->add(
            'financialItemServices',
            CollectionType::class,
            [
                'entry_type' => InvoiceItemServiceType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'mapped' => false,
                'constraints' => new Assert\Valid(),
                'entry_options' => [
                    'multipleTaxes' => $invoice->getPricingMode() === Option::PRICING_MODE_WITHOUT_TAXES,
                ],
            ]
        );

        $builder->add(
            'financialItemSurcharges',
            CollectionType::class,
            [
                'entry_type' => InvoiceItemSurchargeType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'mapped' => false,
                'constraints' => new Assert\Valid(),
                'entry_options' => [
                    'multipleTaxes' => $invoice->getPricingMode() === Option::PRICING_MODE_WITHOUT_TAXES,
                ],
            ]
        );

        $builder->add(
            'attributes',
            InvoiceAttributesType::class,
            [
                'invoice' => $options['data'],
            ]
        );

        $builder->add(
            'invoiceTemplate',
            InvoiceTemplateChoiceType::class,
            [
                'selectedInvoiceTemplate' => $invoice->getInvoiceTemplate(),
            ]
        );

        $builder->add(
            'proformaInvoiceTemplate',
            ProformaInvoiceTemplateChoiceType::class,
            [
                'selectedProformaInvoiceTemplate' => $invoice->getProformaInvoiceTemplate(),
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Invoice::class,
                'display_apply_credit_toggle' => false,
            ]
        );

        $resolver->setAllowedTypes('display_apply_credit_toggle', 'bool');
    }
}
