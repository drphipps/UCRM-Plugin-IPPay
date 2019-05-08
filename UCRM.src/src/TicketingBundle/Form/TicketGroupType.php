<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Form;

use AppBundle\Entity\User;
use AppBundle\Form\AdminChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TicketingBundle\Entity\TicketGroup;

class TicketGroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'required' => true,
            ]
        );

        $builder->add(
            'users',
            AdminChoiceType::class,
            [
                'multiple' => true,
                'required' => false,
                'group_by' => function (User $user) {
                    return $user->getGroup() ? $user->getGroup()->getName() : null;
                },
                'by_reference' => false,
                'users' => isset($options['data']) ? $options['data']->getUsers() : null,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => TicketGroup::class,
            ]
        );
    }
}
