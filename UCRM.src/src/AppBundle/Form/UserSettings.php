<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSettings extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('username', TextType::class);

        $builder->add('email', EmailType::class);

        $builder->add(
            'firstName',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'locale',
            LocaleChoiceType::class,
            [
                'label' => 'Language',
                'placeholder' => 'Use system default.',
                'required' => false,
            ]
        );

        $builder->add(
            'lastName',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'googleCalendarId',
            ChoiceType::class,
            [
                'label' => 'choose calendar for synchronization',
                'placeholder' => $options['google_calendars'] ? 'choose calendar for synchronization' : 'no calendar available',
                'choices' => $options['google_calendars'],
                'choice_translation_domain' => false,
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('google_calendars');
        $resolver->setAllowedTypes('google_calendars', 'array');

        $resolver->setDefaults(
            [
                'data_class' => User::class,
                'validation_groups' => function (FormInterface $formInterface) {
                    /** @var User $data */
                    $user = $formInterface->getData();
                    if ($user->getId()) {
                        return ['Default', 'User'];
                    }

                    return ['Default', 'User', 'newUser'];
                },
                'google_calendars' => [],
            ]
        );
    }
}
