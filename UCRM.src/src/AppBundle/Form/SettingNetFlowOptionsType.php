<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\NetFlowOptionsData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingNetFlowOptionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'netflowAggregationFrequency',
            IntegerType::class,
            [
                'label' => Option::NAMES[Option::NETFLOW_AGGREGATION_FREQUENCY],
            ]
        );

        $builder->add(
            'netflowMinimumUnknownTraffic',
            IntegerType::class,
            [
                'label' => Option::NAMES[Option::NETFLOW_MINIMUM_UNKNOWN_TRAFFIC],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => NetFlowOptionsData::class,
            ]
        );
    }
}
