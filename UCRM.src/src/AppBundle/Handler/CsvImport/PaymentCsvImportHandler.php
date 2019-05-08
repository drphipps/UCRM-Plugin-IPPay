<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Handler\CsvImport;

use AppBundle\Entity\Client;
use AppBundle\Entity\CsvImport;
use AppBundle\Entity\Currency;
use AppBundle\Entity\Payment;
use AppBundle\Facade\CsvImportFacade;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Util\DateTimeFactory;
use Doctrine\ORM\EntityManagerInterface;

class PaymentCsvImportHandler
{
    /**
     * @var PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var CsvImportFacade
     */
    private $csvImportFacade;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        PaymentFacade $paymentFacade,
        CsvImportFacade $csvImportFacade,
        EntityManagerInterface $entityManager
    ) {
        $this->paymentFacade = $paymentFacade;
        $this->csvImportFacade = $csvImportFacade;
        $this->entityManager = $entityManager;
    }

    public function processPaymentImport(array $data, CsvImport $csvImport): void
    {
        $failed = false;
        try {
            $payment = new Payment();
            $payment->setClient($this->getClient($data['client']));
            $payment->setMethod((int) $data['method']);
            $payment->setAmount((float) $data['amount']);
            $payment->setCurrency($this->entityManager->find(Currency::class, $data['currency']));
            $payment->setNote($data['note']);
            $payment->setCreatedDate(
                $data['createdDate'] ? DateTimeFactory::createFromFormat(\DateTime::ATOM, $data['createdDate']) : null
            );
            $payment->setUser($csvImport->getUser());

            $this->paymentFacade->handleCreateMultipleWithoutInvoiceIds([$payment]);
        } catch (\Throwable $exception) {
            $failed = true;

            throw $exception;
        } finally {
            if ($failed) {
                $csvImport->setCountFailure($csvImport->getCountFailure() + 1);
            } else {
                $csvImport->setCountSuccess($csvImport->getCountSuccess() + 1);
            }

            $this->csvImportFacade->handleEdit($csvImport);
        }
    }

    private function getClient(?int $id): ?Client
    {
        if (! $id) {
            return null;
        }

        return $this->entityManager->find(Client::class, $id);
    }
}
