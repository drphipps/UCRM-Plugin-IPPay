<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Fee;
use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\FeesData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingFeesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'lateFeeActive',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::LATE_FEE_ACTIVE],
                'required' => false,
            ]
        );

        $builder->add(
            'lateFeeDelayDays',
            IntegerType::class,
            [
                'label' => Option::NAMES[Option::LATE_FEE_DELAY_DAYS],
                'attr' => [
                    'min' => 0,
                ],
            ]
        );

        $builder->add(
            'lateFeeInvoiceLabel',
            TextType::class,
            [
                'label' => Option::NAMES[Option::LATE_FEE_INVOICE_LABEL],
                'required' => false,
            ]
        );

        $builder->add(
            'lateFeePrice',
            FloatType::class,
            [
                'label' => Option::NAMES[Option::LATE_FEE_PRICE],
            ]
        );

        $builder->add(
            'lateFeePriceType',
            ChoiceType::class,
            [
                'label' => Option::NAMES[Option::LATE_FEE_PRICE_TYPE],
                'choices' => array_flip(Fee::PRICE_TYPES),
            ]
        );

        $builder->add(
            'lateFeeTaxable',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::LATE_FEE_TAXABLE],
                'required' => false,
            ]
        );

        $builder->add(
            'lateFeeTaxId',
            TaxChoiceType::class,
            [
                'label' => Option::NAMES[Option::LATE_FEE_TAX_ID],
                'required' => false,
            ]
        );

        $builder->add(
            'setupFeeInvoiceLabel',
            TextType::class,
            [
                'label' => Option::NAMES[Option::SETUP_FEE_INVOICE_LABEL],
                'required' => false,
            ]
        );

        $builder->add(
            'setupFeeTaxable',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::SETUP_FEE_TAXABLE],
                'required' => false,
            ]
        );

        $builder->add(
            'setupFeeTaxId',
            TaxChoiceType::class,
            [
                'label' => Option::NAMES[Option::SETUP_FEE_TAX_ID],
                'required' => false,
            ]
        );

        $builder->add(
            'earlyTerminationFeeInvoiceLabel',
            TextType::class,
            [
                'label' => Option::NAMES[Option::EARLY_TERMINATION_FEE_INVOICE_LABEL],
                'required' => false,
            ]
        );

        $builder->add(
            'earlyTerminationFeeTaxable',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::EARLY_TERMINATION_FEE_TAXABLE],
                'required' => false,
            ]
        );

        $builder->add(
            'earlyTerminationFeeTaxId',
            TaxChoiceType::class,
            [
                'label' => Option::NAMES[Option::EARLY_TERMINATION_FEE_TAX_ID],
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => FeesData::class,
            ]
        );
    }
}
