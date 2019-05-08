<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Fcc;

use AppBundle\Entity\Service;
use AppBundle\Facade\FccFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class FccBlockIdConsumer extends AbstractConsumer
{
    /**
     * @var FccFacade
     */
    private $fccFacade;

    public function __construct(
        EntityManager $entityManager,
        FccFacade $fccFacade,
        LoggerInterface $logger,
        Options $options
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->fccFacade = $fccFacade;
    }

    protected function getMessageClass(): string
    {
        return FccBlockIdMessage::class;
    }

    public function executeBody(array $data): int
    {
        $service = $this->entityManager->find(Service::class, $data['serviceId']);

        if ($service) {
            $block = $this->fccFacade->findAndUpdateBlock($service);

            if ($block) {
                $this->logger->info(sprintf('FCC Census Block updated for service (%s).', $service->getId()));

                return self::MSG_ACK;
            }

            $this->logger->error(
                sprintf('FCC Census Block update failed for service (%s).', $service->getId())
            );

            return self::MSG_REJECT;
        }

        $this->logger->error(
            sprintf('Not found service (%s).', $data['serviceId'])
        );

        return self::MSG_REJECT;
    }
}
