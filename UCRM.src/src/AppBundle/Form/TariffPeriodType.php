<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\TariffPeriod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TariffPeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'price',
            FloatType::class,
            [
                'required' => false,
            ]
        );

        $builder->add('period', HiddenType::class);

        if ($options['include_period_enabled']) {
            $builder->add(
                'enabled',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'enabled',
                ]
            );
        }

        $builder->setRequired(false);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => TariffPeriod::class,
                'include_period_enabled' => true,
            ]
        );

        $resolver->setAllowedTypes('include_period_enabled', 'boolean');
    }
}
