<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Csv\EntityCsvFactory;

use AppBundle\Component\Csv\CsvBuilder;
use AppBundle\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PaymentQuickBooksCsvFactory
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    public function create(array $ids): string
    {
        $builder = new CsvBuilder();

        $payments = $this->entityManager->getRepository(Payment::class)->getByIds($ids);
        /** @var Payment $payment */
        foreach ($payments as $payment) {
            $descriptionParts = [
                $this->translator->trans(
                    'Method: %method%',
                    [
                        '%method%' => $this->translator->trans($payment->getMethodName()),
                    ]
                ),
            ];

            if ($payment->getClient()) {
                $descriptionParts[] = $this->translator->trans(
                    'Client: %client%',
                    [
                        '%client%' => sprintf(
                             '%s (%d)',
                            $payment->getClient()->getNameForView(),
                            $payment->getClient()->getId()
                        ),
                    ]
                );
            }

            $invoiceNumbers = [];
            foreach ($payment->getPaymentCovers() as $paymentCover) {
                if ($paymentCover->getInvoice()) {
                    $invoiceNumbers[] = $paymentCover->getInvoice()->getInvoiceNumber();
                }
            }
            if ($invoiceNumbers) {
                $descriptionParts[] = $this->translator->trans(
                    'Covers invoice: %invoice%',
                    [
                        '%invoice%' => implode(',', $invoiceNumbers),
                    ]
                );
            }

            $builder->addData(
                [
                    'Date' => $payment->getCreatedDate()->format('n/d/Y'),
                    'Amount' => $payment->getAmount(),
                    'Description' => implode(', ', $descriptionParts),
                ]
            );
        }

        return $builder->getCsv();
    }
}
