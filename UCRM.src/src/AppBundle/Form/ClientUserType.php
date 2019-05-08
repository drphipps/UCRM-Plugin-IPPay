<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'username',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Username',
                    ],
                ]
            )
            ->add(
                'firstName',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'First name',
                    ],
                ]
            )
            ->add(
                'lastName',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Last name',
                    ],
                ]
            )
            ->add(
                'plainPassword',
                PasswordType::class,
                [
                    'label' => 'Password',
                    'required' => false,
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'placeholder' => 'Password',
                    ],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => User::class,
            ]
        );
    }
}
