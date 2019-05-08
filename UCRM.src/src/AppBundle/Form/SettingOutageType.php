<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\OutageData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingOutageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'pingOutageThreshold',
                IntegerType::class,
                [
                    'label' => Option::NAMES[Option::PING_OUTAGE_THRESHOLD],
                ]
            )
            ->add(
                'notificationPingUser',
                AdminChoiceType::class,
                [
                    'label' => Option::NAMES[Option::NOTIFICATION_PING_USER],
                    'required' => false,
                    'user' => isset($options['data']) ? ($options['data']->notificationPingUser ?? null) : null,
                ]
            )
            ->add(
                'notificationPingDown',
                CheckboxType::class,
                [
                    'label' => Option::NAMES[Option::NOTIFICATION_PING_DOWN],
                    'required' => false,
                ]
            )
            ->add(
                'notificationPingUnreachable',
                CheckboxType::class,
                [
                    'label' => Option::NAMES[Option::NOTIFICATION_PING_UNREACHABLE],
                    'required' => false,
                ]
            )
            ->add(
                'notificationPingRepaired',
                CheckboxType::class,
                [
                    'label' => Option::NAMES[Option::NOTIFICATION_PING_REPAIRED],
                    'required' => false,
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => OutageData::class,
            ]
        );
    }
}
