<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Client;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Payment;
use AppBundle\Form\Data\ClientChoiceData;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentType extends AbstractPaymentClientInvoicesType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'method',
            ChoiceType::class,
            [
                'placeholder' => 'Make a choice.',
                'choices' => array_flip(Payment::METHOD_TYPE),
                'preferred_choices' => function ($paymentMethod) {
                    return $paymentMethod !== Payment::METHOD_COURTESY_CREDIT;
                },
            ]
        );

        $builder->add(
            'checkNumber',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'createdDate',
            DateTimeType::class,
            [
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
            ]
        );

        $builder->add(
            'amount',
            FloatType::class,
            [
                'required' => true,
            ]
        );

        $builder->add(
            'currency',
            CurrencyChoiceType::class,
            [
                'required' => false,
                'placeholder' => 'Make a choice.',
                'prefer_used_currencies' => true,
                'organization' => $options['organization'],
            ]
        );

        $builder->add(
            'note',
            TextareaType::class,
            [
                'required' => false,
            ]
        );

        $this->addClientField($builder, $options);

        $builder->add(
            'sendReceipt',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                /** @var Payment|null $data */
                $data = $event->getData();
                $this->invoicesFormModifier($event->getForm(), $data ? $data->getClient() : null);
            }
        );

        $builder->get('client')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $client = $form->getData();
                $payment = $form->getParent()->getData();
                if ($payment && $client && ! $payment->getCurrency()) {
                    $payment->setCurrency($client->getOrganization()->getCurrency());
                }
                $this->invoicesFormModifier($form->getParent(), $client);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Payment::class,
                'set_client_required' => false,
                'client' => null,
            ]
        );

        $resolver->setAllowedTypes('set_client_required', 'bool');
        $resolver->setRequired('organization');
        $resolver->setAllowedTypes('organization', [Organization::class, 'null']);
        $resolver->setAllowedTypes('client', [Client::class, 'null']);
    }

    public function addClientField(FormBuilderInterface $builder, array $options): void
    {
        $clientOptions = [];
        $clientOptions['required'] = $options['set_client_required'];
        $clientOptions['choice_attr'] = function (ClientChoiceData $client) {
            return [
                'data-currency-id' => $client->currencyId,
                'data-has-billing-email' => (int) $client->hasBillingEmail,
            ];
        };
        if ($options['client']) {
            // Disables loading of client choices when client is already chosen and the field is hidden.
            $clientOptions['choices'] = [ClientChoiceData::fromClient($options['client'])];
        }

        $builder->add(
            'client',
            ClientChoiceType::class,
            $clientOptions
        );
    }
}
