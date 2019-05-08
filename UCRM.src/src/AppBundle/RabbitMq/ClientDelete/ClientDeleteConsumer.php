<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\ClientDelete;

use AppBundle\Entity\Client;
use AppBundle\Facade\ClientFacade;
use AppBundle\Facade\Exception\CannotCancelClientSubscriptionException;
use AppBundle\Facade\Exception\CannotDeleteDemoClientException;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\RabbitMq\Exception\RejectStopConsumerException;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ClientDeleteConsumer extends AbstractConsumer
{
    /**
     * @var ClientFacade
     */
    private $clientFacade;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        Options $options,
        ClientFacade $clientFacade
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->clientFacade = $clientFacade;
    }

    protected function getMessageClass(): string
    {
        return ClientDeleteMessage::class;
    }

    protected function executeBody(array $data): int
    {
        $client = $this->entityManager->find(Client::class, $data['clientId']);
        if (! $client) {
            $this->logger->warning(sprintf('Client ID %d not found.', $data['clientId']));

            return self::MSG_REJECT;
        }

        switch ($data['operation']) {
            case ClientDeleteMessage::OPERATION_ARCHIVE:
                $this->clientFacade->handleArchive($client);
                break;
            case ClientDeleteMessage::OPERATION_DELETE:
                try {
                    $this->clientFacade->handleDelete($client);
                } catch (CannotDeleteDemoClientException $exception) {
                    $this->logger->warning(
                        sprintf(
                            'Client ID %d cannot be deleted in demo.',
                            $data['clientId']
                        )
                    );

                    // exception closes EntityManager, consumer needs restart
                    throw new RejectStopConsumerException();
                } catch (CannotCancelClientSubscriptionException $exception) {
                    $this->logger->warning(
                        sprintf(
                            'Failed to cancel subscription "%s" for client ID %d.',
                            $exception->getPaymentPlan()->getName(),
                            $data['clientId']
                        )
                    );

                    // exception closes EntityManager, consumer needs restart
                    throw new RejectStopConsumerException();
                }

                break;
            default:
                $this->logger->warning(sprintf('Operation "%s" not supported.', $data['operation']));

                return self::MSG_REJECT;
        }

        return self::MSG_ACK;
    }
}
