<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Report;

use AppBundle\Facade\ReportDataUsageFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ReportDataUsageConsumer extends AbstractConsumer
{
    /**
     * @var ReportDataUsageFacade
     */
    private $reportDataUsageFacade;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        Options $options,
        ReportDataUsageFacade $reportDataUsageFacade
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->reportDataUsageFacade = $reportDataUsageFacade;
    }

    protected function getMessageClass(): string
    {
        return ReportDataUsageMessage::class;
    }

    public function executeBody(array $data): int
    {
        $status = $this->reportDataUsageFacade->generateReportData($data['user']);

        if ($status) {
            $this->logger->info('Generated data usage report.');
        } else {
            $this->logger->error('Data usage report generation failed (format "%s").');
        }

        return $status ? self::MSG_ACK : self::MSG_REJECT;
    }
}
