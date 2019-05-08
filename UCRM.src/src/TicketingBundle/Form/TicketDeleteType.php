<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TicketingBundle\Form\Data\TicketDeleteData;

class TicketDeleteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['hasEmailFromAddress']) {
            $builder->add(
                'addToBlacklist',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            );

            $builder->add(
                'deleteTickets',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => TicketDeleteData::class,
                'hasEmailFromAddress' => false,
            ]
        );

        $resolver->setAllowedTypes('hasEmailFromAddress', 'boolean');
    }
}
