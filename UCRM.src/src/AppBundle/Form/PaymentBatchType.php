<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Payment;
use AppBundle\Form\Data\PaymentBatchData;
use AppBundle\Form\Data\PaymentBatchItemData;
use AppBundle\Util\Formatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentBatchType extends AbstractType
{
    /**
     * @var Formatter
     */
    private $formatter;

    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'method',
            ChoiceType::class,
            [
                'placeholder' => 'Choose a method',
                'choices' => array_flip(Payment::METHOD_TYPE),
                'preferred_choices' => function ($paymentMethod) {
                    return $paymentMethod !== Payment::METHOD_COURTESY_CREDIT;
                },
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
            'sendReceipt',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Send receipt',
            ]
        );

        $builder->add(
            'payments',
            CollectionType::class,
            [
                'entry_type' => PaymentBatchItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype_data' => new PaymentBatchItemData(),
                'required' => true,
                'delete_empty' => function (?PaymentBatchItemData $itemData) {
                    // if the payment is empty, ignore it and remove from collection
                    $filtered = array_filter(
                        [
                            $itemData->client,
                            $itemData->amount,
                            $itemData->note,
                            $itemData->checkNumber,
                        ],
                        function ($value) {
                            return $value !== null;
                        }
                    );

                    return ! $itemData || empty($filtered);
                },
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => PaymentBatchData::class,
                'class' => Payment::class,
            ]
        );
    }
}
