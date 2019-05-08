<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Plugin;
use AppBundle\Form\Data\PluginConfigurationData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PluginConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'executionPeriod',
            ChoiceType::class,
            [
                'choices' => array_flip(Plugin::EXECUTION_PERIOD_LABELS),
                'placeholder' => 'don\'t execute automatically',
                'required' => false,
            ]
        );

        $builder->add(
            'configuration',
            PluginConfigurationItemsType::class,
            [
                'configuration_items' => $options['configuration_items'],
                'existingFiles' => $options['existingFiles'],
            ]
        );

        $builder->add(
            'save',
            SubmitType::class
        );

        if ($options['show_enable_button']) {
            $builder->add(
                'saveAndEnable',
                SubmitType::class
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => PluginConfigurationData::class,
                'configuration_items' => [],
                'existingFiles' => [],
                'show_enable_button' => false,
            ]
        );

        $resolver->setAllowedTypes('configuration_items', 'array');
        $resolver->setAllowedTypes('show_enable_button', 'bool');
        $resolver->setAllowedTypes('existingFiles', 'array');
    }
}
