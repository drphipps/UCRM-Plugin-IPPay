<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Form\Data\WizardOrganizationData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class WizardOrganizationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'organization',
            WizardOrganizationEntityType::class,
            [
                'constraints' => [
                    new Valid(),
                ],
                'hasFinancialEntities' => $options['hasFinancialEntities'],
                'organization' => $options['organization'],
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
                'expanded' => true,
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => WizardOrganizationData::class,
                'allow_invoice_item_rounding' => false,
                'hasFinancialEntities' => true,
            ]
        );

        $resolver->setAllowedTypes('allow_invoice_item_rounding', 'bool');
        $resolver->setRequired('organization');
        $resolver->setAllowedTypes('organization', Organization::class);
        $resolver->setRequired('hasFinancialEntities');
        $resolver->setAllowedTypes('hasFinancialEntities', 'bool');
    }
}
