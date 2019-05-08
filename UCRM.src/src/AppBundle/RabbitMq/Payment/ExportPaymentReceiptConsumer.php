<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Payment;

use AppBundle\Facade\PaymentReceiptFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ExportPaymentReceiptConsumer extends AbstractConsumer
{
    /**
     * @var PaymentReceiptFacade
     */
    private $paymentReceiptFacade;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        Options $options,
        PaymentReceiptFacade $paymentReceiptFacade
    ) {
        parent::__construct($em, $logger, $options);

        $this->paymentReceiptFacade = $paymentReceiptFacade;
    }

    protected function getMessageClass(): string
    {
        return ExportPaymentReceiptMessage::class;
    }

    public function executeBody(array $data): int
    {
        $status = $this->paymentReceiptFacade->finishReceiptPdfExport($data['download'], $data['payments']);

        if ($status) {
            $this->logger->info(sprintf('Payment receipt export generated (format "pdf").'));
        } else {
            $this->logger->error(sprintf('Payment receipt export failed (format "pdf").'));
        }

        return $status ? self::MSG_ACK : self::MSG_REJECT;
    }
}
