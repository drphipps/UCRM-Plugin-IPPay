<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Component\Validator\Constraints\InvoicesCurrency;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Repository\InvoiceRepository;
use AppBundle\Service\Options;
use AppBundle\Util\Formatter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractPaymentClientInvoicesType extends AbstractType
{
    /**
     * @var Options
     */
    protected $options;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Formatter
     */
    protected $formatter;

    public function __construct(Options $options, TranslatorInterface $translator, Formatter $formatter)
    {
        $this->options = $options;
        $this->translator = $translator;
        $this->formatter = $formatter;
    }

    protected function invoicesFormModifier(FormInterface $form, ?Client $client): void
    {
        $options = [
            'class' => Invoice::class,
            'choice_label' => function (Invoice $invoice) {
                return sprintf(
                    '%s - %s',
                    $invoice->getInvoiceNumber(),
                    $this->formatter->formatCurrency(
                        $invoice->getAmountToPay(),
                        $invoice->getCurrency()->getCode(),
                        $invoice->getOrganization()->getLocale()
                    )
                );
            },
            'required' => false,
            'label' => 'Add to invoices',
            'mapped' => false,
            'multiple' => true,
            'expanded' => true,
            'choice_attr' => function (Invoice $invoice) {
                return [
                    'data-amount' => $invoice->getAmountToPay(),
                    'data-currency-code' => $invoice->getCurrency()->getCode(),
                    'data-currency-id' => $invoice->getCurrency()->getId(),
                ];
            },
            'constraints' => new InvoicesCurrency(),
        ];

        if ($client) {
            $options['query_builder'] = function (InvoiceRepository $repository) use ($client) {
                return $repository->getClientUnpaidInvoicesQueryBuilder($client);
            };
        } else {
            $options['choices'] = [];
        }

        $form->add('invoices', EntityType::class, $options);
    }
}
