<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Payment;

use AppBundle\Facade\PaymentOverviewFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ExportPaymentOverviewConsumer extends AbstractConsumer
{
    /**
     * @var PaymentOverviewFacade
     */
    private $paymentOverviewFacade;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        Options $options,
        PaymentOverviewFacade $paymentOverviewFacade
    ) {
        parent::__construct($em, $logger, $options);

        $this->paymentOverviewFacade = $paymentOverviewFacade;
    }

    protected function getMessageClass(): string
    {
        return ExportPaymentOverviewMessage::class;
    }

    public function executeBody(array $data): int
    {
        switch ($data['format']) {
            case ExportPaymentOverviewMessage::FORMAT_PDF:
                $status = $this->paymentOverviewFacade->finishPdfOverviewExport($data['download'], $data['payments']);
                break;
            case ExportPaymentOverviewMessage::FORMAT_CSV:
                $status = $this->paymentOverviewFacade->finishCsvOverviewExport($data['download'], $data['payments']);
                break;
            case ExportPaymentOverviewMessage::FORMAT_QUICKBOOKS_CSV:
                $status = $this->paymentOverviewFacade->finishQuickBooksCsvExport($data['download'], $data['payments']);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Export format ("%s") not supported.', $data['format']));
        }

        if ($status) {
            $this->logger->info(sprintf('Generated payment overview export (format "%s").', $data['format']));
        } else {
            $this->logger->error(sprintf('Payment overview export failed (format "%s").', $data['format']));
        }

        return $status ? self::MSG_ACK : self::MSG_REJECT;
    }
}
