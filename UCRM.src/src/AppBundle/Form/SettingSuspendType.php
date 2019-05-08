<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\SuspendData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingSuspendType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'suspendEnabled',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::SUSPEND_ENABLED],
                'required' => false,
            ]
        );

        $builder->add(
            'stopServiceDue',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::STOP_SERVICE_DUE],
                'required' => false,
            ]
        );

        $builder->add(
            'stopServiceDueDays',
            IntegerType::class,
            [
                'label' => Option::NAMES[Option::STOP_SERVICE_DUE_DAYS],
                'attr' => [
                    'min' => 0,
                ],
            ]
        );

        $builder->add(
            'suspensionMinimumUnpaidAmount',
            NumberType::class,
            [
                'label' => Option::NAMES[Option::SUSPENSION_MINIMUM_UNPAID_AMOUNT],
            ]
        );

        $builder->add(
            'suspensionEnablePostpone',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::SUSPENSION_ENABLE_POSTPONE],
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => SuspendData::class,
            ]
        );
    }
}
