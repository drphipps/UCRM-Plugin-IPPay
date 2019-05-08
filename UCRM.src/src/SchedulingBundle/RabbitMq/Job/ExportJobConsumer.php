<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\RabbitMq\Job;

use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use SchedulingBundle\Service\Facade\JobFacade;

class ExportJobConsumer extends AbstractConsumer
{
    /**
     * @var JobFacade
     */
    private $jobFacade;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        Options $options,
        JobFacade $jobFacade
    ) {
        parent::__construct($em, $logger, $options);

        $this->jobFacade = $jobFacade;
    }

    protected function getMessageClass(): string
    {
        return ExportJobMessage::class;
    }

    public function executeBody(array $data): int
    {
        switch ($data['format']) {
            case ExportJobMessage::FORMAT_PDF:
                $status = $this->jobFacade->finishPdfExport($data['download'], $data['jobs']);
                break;
            case ExportJobMessage::FORMAT_CSV:
                $status = $this->jobFacade->finishCsvExport($data['download'], $data['jobs']);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Export format ("%s") not supported.', $data['format']));
        }

        if ($status) {
            $this->logger->info(sprintf('Generated job export (format "%s").', $data['format']));
        } else {
            $this->logger->error(sprintf('Job export failed (format "%s").', $data['format']));
        }

        return $status ? self::MSG_ACK : self::MSG_REJECT;
    }
}
