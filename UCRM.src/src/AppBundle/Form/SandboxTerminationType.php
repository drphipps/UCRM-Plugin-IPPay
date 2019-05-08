<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Form\Data\SandboxTerminationData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SandboxTerminationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'keepClients',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Clients',
            ]
        );

        $builder->add(
            'resetInvitationEmails',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Mark all clients as "Not invited to Client Zone" (so you can invite everybody easily)',
            ]
        );

        $builder->add(
            'keepServices',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Client services',
            ]
        );

        $builder->add(
            'keepInvoices',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Invoices',
            ]
        );

        $builder->add(
            'resetNextInvoicingDay',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Reset Next Invoicing Day',
            ]
        );

        $builder->add(
            'resetInvoiceEmails',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Mark all invoices as "not sent" (so you can send them all out easily)',
            ]
        );

        $builder->add(
            'keepPayments',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Payments, Credits, Refunds',
            ]
        );

        $builder->add(
            'keepTickets',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Tickets',
            ]
        );

        $builder->add(
            'keepJobs',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Jobs',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => SandboxTerminationData::class,
            ]
        );
    }
}
