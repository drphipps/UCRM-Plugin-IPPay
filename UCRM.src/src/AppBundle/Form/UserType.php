<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\User;
use AppBundle\Entity\UserGroup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TicketingBundle\Form\TicketGroupChoiceType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'username',
            TextType::class
        );

        $builder->add(
            'email',
            EmailType::class
        );

        $builder->add(
            'firstName',
            TextType::class,
            [
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
            'isActive',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'locale',
            LocaleChoiceType::class,
            [
                'label' => 'Language',
                'placeholder' => 'Make a choice.',
                'required' => false,
            ]
        );

        $builder->add(
            'group',
            EntityType::class,
            [
                'class' => UserGroup::class,
                'choice_label' => 'Group',
            ]
        );

        $builder->add(
            'plainPassword',
            PasswordType::class,
            [
                'label' => 'Password',
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
            ]
        );

        if ($builder->getData() && $builder->getData()->getId()) {
            $builder->get('plainPassword')->setRequired(false);
        }

        if ($options['include_ticket_groups']) {
            $builder->add(
                'ticketGroups',
                TicketGroupChoiceType::class,
                [
                    'label' => 'User groups',
                    'multiple' => true,
                    'required' => false,
                    'by_reference' => false,
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
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
                'include_ticket_groups' => false,
            ]
        );

        $resolver->setAllowedTypes('include_ticket_groups', 'bool');
    }
}
