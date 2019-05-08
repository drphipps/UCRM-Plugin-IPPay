<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Form\Data\ServiceDataUsageData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceDataUsageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'date',
            DateType::class,
            [
                'widget' => 'single_text',
                'html5' => false,
            ]
        );

        $builder->add(
            'period',
            ChoiceType::class,
            [
                'choices' => $options['servicePeriods'],
                'choice_translation_domain' => false,
            ]
        );

        $builder->add(
            'download',
            FloatType::class,
            [
                'label' => 'Download',
            ]
        );

        $builder->add(
            'upload',
            FloatType::class,
            [
                'label' => 'Upload',
            ]
        );

        $builder->add(
            'editType',
            ChoiceType::class,
            [
                'label' => 'Edit type',
                'choices' => [
                    'Billing period' => ServiceDataUsageData::EDIT_TYPE_PERIOD,
                    'Specific date' => ServiceDataUsageData::EDIT_TYPE_DATE,
                ],
                'expanded' => true,
                'data' => 'period',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ServiceDataUsageData::class,
                'servicePeriods' => [],
            ]
        );

        $resolver->setAllowedTypes('servicePeriods', 'array');
    }
}
