<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Form;

use AppBundle\Entity\User;
use AppBundle\Form\AdminChoiceType;
use AppBundle\Form\ClientChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TicketingBundle\Form\Data\TicketAssignData;

class TicketAssignType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'assignedUser',
            AdminChoiceType::class,
            [
                'required' => false,
                'group_by' => function (User $user) {
                    return $user->getGroup() ? $user->getGroup()->getName() : null;
                },
                'user' => isset($options['data']) ? $options['data']->assignedUser : null,
            ]
        );

        $builder->add(
            'assignedClient',
            ClientChoiceType::class,
            [
                'required' => false,
                'include_leads' => true,
            ]
        );

        if ($options['include_add_contact_option']) {
            $builder->add(
                'addContact',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'Add to client\'s contacts',
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => TicketAssignData::class,
                'include_add_contact_option' => false,
            ]
        );

        $resolver->setAllowedTypes('include_add_contact_option', 'bool');
    }
}
