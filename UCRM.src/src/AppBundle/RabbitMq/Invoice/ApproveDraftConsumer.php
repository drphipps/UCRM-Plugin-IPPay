<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Invoice;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\General;
use AppBundle\Facade\InvoiceFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\RabbitMq\Exception\RejectRequeueStopConsumerException;
use AppBundle\Service\Options;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ApproveDraftConsumer extends AbstractConsumer
{
    /**
     * @var InvoiceFacade
     */
    private $invoiceFacade;

    public function __construct(
        InvoiceFacade $invoiceFacade,
        EntityManagerInterface $em,
        Options $options,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger, $options);

        $this->invoiceFacade = $invoiceFacade;
    }

    protected function getMessageClass(): string
    {
        return ApproveDraftMessage::class;
    }

    public function executeBody(array $data): int
    {
        if ($this->options->getGeneral(General::INVOICE_TOTALS_MIGRATION_COMPLETE) !== '1') {
            sleep(1);

            return self::MSG_REJECT_REQUEUE;
        }

        $invoice = $this->entityManager->find(Invoice::class, $data['invoice']);

        if (! $invoice) {
            $this->logger->warning(sprintf('Draft %d not found.', $data['invoice']));

            return self::MSG_REJECT;
        }

        try {
            if (! $this->invoiceFacade->handleApprove($invoice)) {
                $this->logger->warning(sprintf('Could not approve draft %d.', $data['invoice']));

                return self::MSG_REJECT;
            }
        } catch (UniqueConstraintViolationException $exception) {
            $this->logger->error($exception->getMessage());

            throw new RejectRequeueStopConsumerException();
        }

        $this->logger->info(sprintf('Approved draft %d.', $data['invoice']));

        return self::MSG_ACK;
    }
}
