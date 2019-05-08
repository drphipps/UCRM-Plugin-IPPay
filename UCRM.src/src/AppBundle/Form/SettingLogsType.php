<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\LogsData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingLogsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'logLifetimeDevice',
                IntegerType::class,
                [
                    'label' => Option::NAMES[Option::LOG_LIFETIME_DEVICE],
                    'attr' => [
                        'min' => 1,
                    ],
                ]
            )
            ->add(
                'logLifetimeServiceDevice',
                IntegerType::class,
                [
                    'label' => Option::NAMES[Option::LOG_LIFETIME_SERVICE_DEVICE],
                    'attr' => [
                        'min' => 1,
                    ],
                ]
            )
            ->add(
                'logLifetimeEmail',
                IntegerType::class,
                [
                    'label' => Option::NAMES[Option::LOG_LIFETIME_EMAIL],
                    'attr' => [
                        'min' => 1,
                    ],
                ]
            )
            ->add(
                'logLifetimeEntity',
                IntegerType::class,
                [
                    'label' => Option::NAMES[Option::LOG_LIFETIME_ENTITY],
                    'attr' => [
                        'min' => 1,
                    ],
                ]
            )
            ->add(
                'headerNotificationsLifetime',
                IntegerType::class,
                [
                    'label' => Option::NAMES[Option::HEADER_NOTIFICATIONS_LIFETIME],
                    'attr' => [
                        'min' => 1,
                    ],
                ]
            )
            ->add(
                'save',
                SubmitType::class
            )
            ->add(
                'saveAndPurge',
                SubmitType::class
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => LogsData::class,
            ]
        );
    }
}
