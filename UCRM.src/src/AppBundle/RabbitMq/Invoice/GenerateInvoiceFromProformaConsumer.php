<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Invoice;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\General;
use AppBundle\Facade\ProformaInvoiceFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\RabbitMq\Exception\RejectRequeueStopConsumerException;
use AppBundle\Service\Options;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class GenerateInvoiceFromProformaConsumer extends AbstractConsumer
{
    /**
     * @var ProformaInvoiceFacade
     */
    private $proformaInvoiceFacade;

    public function __construct(
        EntityManagerInterface $entityManager,
        Options $options,
        LoggerInterface $logger,
        ProformaInvoiceFacade $proformaInvoiceFacade
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->proformaInvoiceFacade = $proformaInvoiceFacade;
    }

    protected function getMessageClass(): string
    {
        return GenerateInvoiceFromProformaMessage::class;
    }

    public function executeBody(array $data): int
    {
        if ($this->options->getGeneral(General::INVOICE_TOTALS_MIGRATION_COMPLETE) !== '1') {
            sleep(1);

            return self::MSG_REJECT_REQUEUE;
        }

        $proformaInvoice = $this->entityManager->find(Invoice::class, $data['proformaInvoiceId']);

        if (! $proformaInvoice) {
            $this->logger->error(sprintf('Invoice ID %d not found.', $data['proformaInvoiceId']));

            return self::MSG_REJECT;
        }

        if (! $proformaInvoice->isProforma()) {
            $this->logger->error('Can only generate invoice from processed proformas.');

            return self::MSG_REJECT;
        }

        if ($proformaInvoice->getGeneratedInvoice()) {
            $this->logger->error('Invoice was already created from this proforma.');

            return self::MSG_REJECT;
        }

        try {
            $this->proformaInvoiceFacade->createInvoiceFromProforma($proformaInvoice);
        } catch (UniqueConstraintViolationException $exception) {
            $this->logger->error($exception->getMessage());

            throw new RejectRequeueStopConsumerException();
        }

        $this->logger->info(sprintf('Created regular invoice from proforma ID %d', $data['proformaInvoiceId']));

        return self::MSG_ACK;
    }
}
