<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Form\Data\Settings\MailerLimiterData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingMailerLimiterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'useAntiflood',
            CheckboxType::class,
            [
                'label' => 'Use AntiFlood',
                'required' => false,
            ]
        );

        $builder->add(
            'mailerAntifloodLimitCount',
            IntegerType::class,
            [
                'label' => 'Activation threshold',
                'required' => true,
            ]
        );

        $builder->add(
            'mailerAntifloodSleepTime',
            IntegerType::class,
            [
                'label' => 'Pause time',
                'required' => true,
            ]
        );

        $builder->add(
            'useThrottler',
            CheckboxType::class,
            [
                'label' => 'Use Throttler',
                'required' => false,
            ]
        );

        $builder->add(
            'mailerThrottlerLimitCount',
            IntegerType::class,
            [
                'label' => 'Message limit',
                'required' => true,
            ]
        );

        $builder->add(
            'mailerThrottlerLimitTime',
            IntegerType::class,
            [
                'label' => 'Duration',
                'required' => true,
            ]
        );

        $builder->add(
            'mailerThrottlerLimitTimeUnit',
            ChoiceType::class,
            [
                'required' => true,
                'choices' => MailerLimiterData::MAILER_THROTTLER_LIMIT_TIME_UNITS,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => MailerLimiterData::class,
            ]
        );
    }
}
