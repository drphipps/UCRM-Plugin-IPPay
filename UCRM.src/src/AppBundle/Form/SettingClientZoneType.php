<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\ClientZoneData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingClientZoneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'clientZoneReactivation',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::CLIENT_ZONE_REACTIVATION],
                'required' => false,
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

        $builder->add(
            'ticketingEnabled',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::TICKETING_ENABLED],
                'required' => false,
            ]
        );

        $builder->add(
            'subscriptionsEnabledCustom',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::SUBSCRIPTIONS_ENABLED_CUSTOM],
                'required' => false,
            ]
        );

        $builder->add(
            'subscriptionsEnabledLinked',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::SUBSCRIPTIONS_ENABLED_LINKED],
                'required' => false,
            ]
        );

        $builder->add(
            'paymentAmountChange',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::CLIENT_ZONE_PAYMENT_AMOUNT_CHANGE],
                'required' => false,
            ]
        );

        $builder->add(
            'paymentDetails',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::CLIENT_ZONE_PAYMENT_DETAILS],
                'required' => false,
            ]
        );

        $builder->add(
            'servicePlanShapingInformation',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::CLIENT_ZONE_SERVICE_PLAN_SHAPING_INFORMATION],
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ClientZoneData::class,
            ]
        );
    }
}
