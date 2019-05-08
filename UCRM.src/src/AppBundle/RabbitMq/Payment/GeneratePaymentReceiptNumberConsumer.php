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

class GeneratePaymentReceiptNumberConsumer extends AbstractConsumer
{
    /**
     * @var PaymentReceiptFacade
     */
    private $paymentReceiptFacade;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        Options $options,
        PaymentReceiptFacade $paymentReceiptFacade
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->paymentReceiptFacade = $paymentReceiptFacade;
    }

    protected function getMessageClass(): string
    {
        return GeneratePaymentReceiptNumberMessage::class;
    }

    public function executeBody(array $data): int
    {
        $this->logger->info(
            sprintf(
                'Generating payment receipt numbers for payments: "%s"',
                implode(',', $data['payments'])
            )
        );
        $this->paymentReceiptFacade->generateReceiptNumbers($data['payments']);
        $this->logger->info(sprintf('Payment receipt numbers generated.'));

        return self::MSG_ACK;
    }
}
