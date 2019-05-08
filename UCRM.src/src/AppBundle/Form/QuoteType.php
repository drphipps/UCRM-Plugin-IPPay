<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Financial\QuoteItemOther;
use AppBundle\Entity\Option;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class QuoteType extends FinancialType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        /** @var Quote $quote */
        $quote = $options['data'];

        $builder->add(
            'quoteNumber',
            TextType::class,
            [
                'required' => true,
            ]
        );

        $builder->add(
            'financialItemFees',
            CollectionType::class,
            [
                'entry_type' => QuoteItemFeeType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'mapped' => false,
                'constraints' => new Assert\Valid(),
                'entry_options' => [
                    'multipleTaxes' => $quote->getPricingMode() === Option::PRICING_MODE_WITHOUT_TAXES,
                ],
            ]
        );

        $quoteItemOtherPrototype = new QuoteItemOther();
        $quoteItemOtherPrototype->setPrice(0.0);
        $builder->add(
            'financialItemOthers',
            CollectionType::class,
            [
                'entry_type' => QuoteItemOtherType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'mapped' => false,
                'constraints' => new Assert\Valid(),
                'entry_options' => [
                    'multipleTaxes' => $quote->getPricingMode() === Option::PRICING_MODE_WITHOUT_TAXES,
                ],
                'prototype_data' => $quoteItemOtherPrototype,
            ]
        );

        $builder->add(
            'financialItemProducts',
            CollectionType::class,
            [
                'entry_type' => QuoteItemProductType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'mapped' => false,
                'constraints' => new Assert\Valid(),
                'entry_options' => [
                    'multipleTaxes' => $quote->getPricingMode() === Option::PRICING_MODE_WITHOUT_TAXES,
                ],
            ]
        );

        $builder->add(
            'financialItemServices',
            CollectionType::class,
            [
                'entry_type' => QuoteItemServiceType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'mapped' => false,
                'constraints' => new Assert\Valid(),
                'entry_options' => [
                    'multipleTaxes' => $quote->getPricingMode() === Option::PRICING_MODE_WITHOUT_TAXES,
                ],
            ]
        );

        $builder->add(
            'financialItemSurcharges',
            CollectionType::class,
            [
                'entry_type' => QuoteItemSurchargeType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'mapped' => false,
                'constraints' => new Assert\Valid(),
                'entry_options' => [
                    'multipleTaxes' => $quote->getPricingMode() === Option::PRICING_MODE_WITHOUT_TAXES,
                ],
            ]
        );

        $builder->add(
            'quoteTemplate',
            QuoteTemplateChoiceType::class,
            [
                'selectedQuoteTemplate' => $quote->getQuoteTemplate(),
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Quote::class,
            ]
        );
    }
}
