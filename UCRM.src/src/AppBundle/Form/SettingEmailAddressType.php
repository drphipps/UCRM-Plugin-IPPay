<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\EmailAddressesData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingEmailAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'mailerSenderAddress',
            TextType::class,
            [
                'label' => Option::NAMES[Option::MAILER_SENDER_ADDRESS],
                'required' => false,
            ]
        );

        $builder->add(
            'supportEmailAddress',
            TextType::class,
            [
                'label' => Option::NAMES[Option::SUPPORT_EMAIL_ADDRESS],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationEmailAddress',
            TextType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_EMAIL_ADDRESS],
                'required' => false,
            ]
        );
    }

    /**
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => EmailAddressesData::class,
            ]
        );
    }
}
