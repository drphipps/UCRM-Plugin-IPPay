<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Organization;
use AppBundle\Entity\Tariff;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TariffType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'attr' => [
                    'placeholder' => 'E.g. Internet',
                ],
            ]
        );

        if ($options['include_organization_select']) {
            $builder->add(
                'organization',
                OrganizationChoiceType::class,
                [
                    'choice_attr' => function (Organization $organization) {
                        return [
                            'data-currency-code' => $organization->getCurrency()
                                ? $organization->getCurrency()->getCode()
                                : '',
                        ];
                    },
                ]
            );
        }

        $builder->add(
            'invoiceLabel',
            TextType::class,
            [
                'required' => false,
                'label' => 'Invoice item label',
            ]
        );

        $builder->add(
            'downloadSpeed',
            NumberType::class,
            [
                'required' => false,
                'label' => 'Download speed',
            ]
        );

        $builder->add(
            'downloadBurst',
            NumberType::class,
            [
                'required' => false,
                'label' => 'Download burst',
            ]
        );

        $builder->add(
            'uploadSpeed',
            NumberType::class,
            [
                'required' => false,
                'label' => 'Upload speed',
            ]
        );

        $builder->add(
            'uploadBurst',
            NumberType::class,
            [
                'required' => false,
                'label' => 'Upload burst',
            ]
        );

        $builder->add(
            'dataUsageLimit',
            NumberType::class,
            [
                'required' => false,
                'label' => 'Data usage limit',
            ]
        );

        $builder->add(
            'taxable',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'tax',
            TaxChoiceType::class,
            [
                'required' => false,
                'placeholder' => 'Choose a tax',
            ]
        );

        $builder->add(
            'periods',
            CollectionType::class,
            [
                'entry_type' => TariffPeriodType::class,
                'error_bubbling' => false,
                'entry_options' => [
                    'include_period_enabled' => $options['include_period_enabled'],
                ],
            ]
        );

        $builder->add(
            'minimumContractLengthMonths',
            IntegerType::class,
            [
                'label' => 'Minimum contract length',
                'required' => false,
            ]
        );

        $builder->add(
            'setupFee',
            FloatType::class,
            [
                'label' => 'Setup fee',
                'required' => false,
            ]
        );

        $builder->add(
            'earlyTerminationFee',
            FloatType::class,
            [
                'label' => 'Early termination fee',
                'required' => false,
            ]
        );

        $builder->add(
            'includedInFccReports',
            CheckboxType::class,
            [
                'label' => 'Include in FCC reports',
                'required' => false,
            ]
        );

        $builder->add(
            'technologyOfTransmission',
            ChoiceType::class,
            [
                'choices' => array_flip(Tariff::TRANSMISSION_TECHNOLOGIES),
                'placeholder' => 'Make a choice.',
                'required' => false,
                'choice_translation_domain' => false,
            ]
        );

        $builder->add(
            'fccServiceType',
            ChoiceType::class,
            [
                'choices' => array_flip(Tariff::FCC_SERVICE_TYPES),
                'placeholder' => 'Make a choice.',
                'required' => false,
                'label' => 'Service type',
            ]
        );

        $builder->add(
            'maximumContractualDownstreamBandwidth',
            NumberType::class,
            [
                'required' => false,
                'label' => 'Maximum Contractual Downstream Bandwidth',
            ]
        );

        $builder->add(
            'maximumContractualUpstreamBandwidth',
            NumberType::class,
            [
                'required' => false,
                'label' => 'Maximum Contractual Upstream Bandwidth',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Tariff::class,
                'include_organization_select' => false,
                'include_period_enabled' => true,
            ]
        );

        $resolver->setAllowedTypes('include_organization_select', 'boolean');
        $resolver->setAllowedTypes('include_period_enabled', 'boolean');
    }
}
