<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TicketingBundle\Form\Data\TicketImapEmailBlacklistMultipleData;

class TicketImapEmailBlacklistMultipleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'emailAddresses',
            TextareaType::class,
            [
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'One address per line',
                ],
                'required' => true,
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => TicketImapEmailBlacklistMultipleData::class,
            ]
        );
    }
}
