<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TicketingBundle\Entity\TicketImapInbox;

class TicketImapInboxConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'ticketGroup',
            TicketGroupChoiceType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'importStartDate',
            DateType::class,
            [
                'html5' => false,
                'widget' => 'single_text',
            ]
        );

        $builder->add(
            'enabled',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Import enabled',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => TicketImapInbox::class,
            ]
        );
    }
}
