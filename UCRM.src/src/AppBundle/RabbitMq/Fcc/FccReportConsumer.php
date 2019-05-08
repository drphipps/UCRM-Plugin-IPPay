<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Fcc;

use AppBundle\Facade\FccFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\RabbitMq\Exception\RejectStopConsumerException;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class FccReportConsumer extends AbstractConsumer
{
    /**
     * @var FccFacade
     */
    private $fccFacade;

    public function __construct(
        FccFacade $fccFacade,
        EntityManagerInterface $em,
        Options $options,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger, $options);

        $this->fccFacade = $fccFacade;
    }

    protected function getMessageClass(): string
    {
        return FccReportMessage::class;
    }

    public function executeBody(array $data): int
    {
        switch ($data['type']) {
            case FccReportMessage::TYPE_FIXED_BROADBAND_DEPLOYMENT:
                $status = $this->fccFacade->finishFixedBroadbandDeploymentReport(
                    $data['download'],
                    $data['organizations']
                );
                break;
            case FccReportMessage::TYPE_FIXED_BROADBAND_SUBSCRIPTION:
                $status = $this->fccFacade->finishFixedBroadbandSubscriptionReport(
                    $data['download'],
                    $data['organizations']
                );
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Report type ("%s") not supported.', $data['type']));
        }

        if ($status) {
            $this->logger->info(sprintf('Generated FCC report (%s).', $data['type']));
        } else {
            $this->logger->error(sprintf('FCC report failed to generate (%s).', $data['type']));
        }

        if (! $status) {
            throw new RejectStopConsumerException();
        }

        return self::MSG_ACK;
    }
}
