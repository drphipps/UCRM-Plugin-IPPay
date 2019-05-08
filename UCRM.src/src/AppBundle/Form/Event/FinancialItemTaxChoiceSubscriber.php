<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Event;

use AppBundle\Entity\Financial\FinancialItemInterface;
use AppBundle\Form\TaxChoiceType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FinancialItemTaxChoiceSubscriber implements EventSubscriberInterface
{
    /**
     * @var bool
     */
    private $multipleTaxes;

    public function __construct(bool $multipleTaxes)
    {
        $this->multipleTaxes = $multipleTaxes;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        /** @var FinancialItemInterface|null $item */
        $item = $event->getData();

        if ($this->multipleTaxes) {
            $taxes = $item
                ? [
                    $item->getTax1(),
                    $item->getTax2(),
                    $item->getTax3(),
                ]
                : [];

            $event->getForm()->add(
                'tax',
                TaxChoiceType::class,
                [
                    'required' => false,
                    'multiple' => true,
                    'mapped' => false,
                    'taxes' => array_filter($taxes),
                    'attr' => [
                        'placeholder' => 'Make a choice.',
                        'data-select2-limit' => 3,
                    ],
                    'data' => $taxes,
                ]
            );
        } else {
            $taxes = [$item ? $item->getTax1() : null];

            $event->getForm()->add(
                'tax',
                TaxChoiceType::class,
                [
                    'required' => false,
                    'multiple' => false,
                    'mapped' => false,
                    'taxes' => array_filter($taxes),
                    'forceArrayModel' => true,
                    'data' => $taxes,
                ]
            );
        }
    }
}
