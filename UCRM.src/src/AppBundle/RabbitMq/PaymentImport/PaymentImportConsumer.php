<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\PaymentImport;

use AppBundle\Entity\CsvImport;
use AppBundle\Handler\CsvImport\PaymentCsvImportHandler;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PaymentImportConsumer extends AbstractConsumer
{
    /**
     * @var PaymentCsvImportHandler
     */
    private $paymentCsvImportHandler;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        Options $options,
        PaymentCsvImportHandler $paymentCsvImportHandler
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->paymentCsvImportHandler = $paymentCsvImportHandler;
    }

    protected function getMessageClass(): string
    {
        return PaymentImportMessage::class;
    }

    public function executeBody(array $data): int
    {
        $csvImport = $this->entityManager->getRepository(CsvImport::class)->findOneBy(
            [
                'uuid' => $data['csvImportUuid'],
            ]
        );
        if (! $csvImport) {
            $this->logger->warning(sprintf('CSV import UUID %s not found.', $data['csvImportUuid']));

            return self::MSG_REJECT;
        }

        $this->paymentCsvImportHandler->processPaymentImport($data['payment'], $csvImport);

        return self::MSG_ACK;
    }
}
