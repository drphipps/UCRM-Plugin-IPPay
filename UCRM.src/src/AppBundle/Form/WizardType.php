<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Form\Data\WizardData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WizardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'username',
            TextType::class,
            [
                'required' => true,
                'attr' => [
                    'placeholder' => 'Username',
                ],
            ]
        );

        $builder->add(
            'password',
            PasswordType::class,
            [
                'required' => true,
                'label' => 'Password',
                'attr' => [
                    'placeholder' => 'Password',
                    'autocomplete' => 'new-password',
                ],
            ]
        );

        $builder->add(
            'email',
            EmailType::class,
            [
                'required' => true,
                'attr' => [
                    'placeholder' => 'Email',
                ],
            ]
        );

        $builder->add(
            'firstName',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'First name',
                ],
            ]
        );

        $builder->add(
            'lastName',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Last name',
                ],
            ]
        );

        $builder->add(
            'locale',
            LocaleChoiceType::class,
            [
                'label' => 'Language',
            ]
        );

        $builder->add(
            'timezone',
            TimezoneChoiceType::class
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => WizardData::class,
            ]
        );
    }
}
