<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Csv\EntityCsvFactory;

use AppBundle\Component\Csv\CsvBuilder;
use AppBundle\Entity\Payment;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PaymentCsvFactory
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        Formatter $formatter,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->formatter = $formatter;
        $this->translator = $translator;
    }

    public function create(array $ids): string
    {
        $builder = new CsvBuilder();

        $payments = $this->entityManager->getRepository(Payment::class)->getByIds($ids);
        /** @var Payment $payment */
        foreach ($payments as $payment) {
            $data = [
                'Method' => $this->translator->trans($payment->getMethodName()),
                'Client name' => $payment->getClient() ? $payment->getClient()->getNameForView() : null,
                'Amount' => $this->formatter->formatCurrency(
                    $payment->getAmount(),
                    $payment->getCurrency()->getCode()
                ),
                'Created date' => $this->formatter->formatDate($payment->getCreatedDate(), Formatter::MEDIUM, Formatter::MEDIUM),
                'Organization name' => $payment->getClient() ? $payment->getClient()->getOrganization()->getName() : null,
                'Client ID' => $payment->getClient() ? $payment->getClient()->getId() : null,
                'Amount (numeric)' => $payment->getAmount(),
                'Currency' => $payment->getCurrency() ? $payment->getCurrency()->getCode() : null,
                'Note' => $payment->getNote(),
                'Admin name' => $payment->getUser() ? $payment->getUser()->getNameForView() : null,
                'Admin ID' => $payment->getUser() ? $payment->getUser()->getId() : null,
            ];

            $builder->addData($data);
        }

        return $builder->getCsv();
    }
}
