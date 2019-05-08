<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Form\Data\ClientChoiceData;
use AppBundle\Form\Data\PaymentBatchItemData;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentBatchItemType extends AbstractPaymentClientInvoicesType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'client',
            ClientChoiceType::class,
            [
                'required' => false,
                'placeholder' => 'Choose a client',
                'choice_attr' => function (ClientChoiceData $client) {
                    return [
                        'data-currency-id' => $client->currencyId,
                        'data-currency-symbol' => $client->currencySymbol,
                        'data-has-billing-email' => (int) $client->hasBillingEmail,
                    ];
                },
            ]
        );

        $builder->add(
            'amount',
            FloatType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'checkNumber',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Check number',
                ],
            ]
        );

        $builder->add(
            'note',
            TextareaType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Note',
                ],
            ]
        );

        $builder->add(
            'sendReceipt',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Send receipt',
            ]
        );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                /** @var PaymentBatchItemData|null $data */
                $data = $event->getData();
                $this->invoicesFormModifier($event->getForm(), $data ? $data->client : null);
            }
        );

        $builder->get('client')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $this->invoicesFormModifier($form->getParent(), $form->getData());
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => PaymentBatchItemData::class,
            ]
        );
    }
}
