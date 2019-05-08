<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\QosData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingQosType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'qosEnabled',
                CheckboxType::class,
                [
                    'label' => Option::NAMES[Option::QOS_ENABLED],
                    'required' => false,
                ]
            )
            ->add(
                'qosSyncType',
                ChoiceType::class,
                [
                    'label' => Option::NAMES[Option::QOS_SYNC_TYPE],
                    'choices' => array_flip(Option::QOS_SYNC_TYPES),
                ]
            )
            ->add(
                'qosDestination',
                ChoiceType::class,
                [
                    'label' => Option::NAMES[Option::QOS_DESTINATION],
                    'choices' => array_flip(Option::QOS_DESTINATIONS),
                ]
            )
            ->add(
                'qosInterfaceAirOs',
                ChoiceType::class,
                [
                    'label' => Option::NAMES[Option::QOS_INTERFACE_AIR_OS],
                    'choices' => array_flip(Option::QOS_INTERFACE_AIR_OS_TYPES),
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => QosData::class,
            ]
        );
    }
}
