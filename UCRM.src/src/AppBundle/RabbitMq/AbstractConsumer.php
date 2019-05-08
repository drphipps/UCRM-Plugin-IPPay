<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq;

use AppBundle\RabbitMq\Exception\InvalidMessageException;
use AppBundle\RabbitMq\Exception\RejectRequeueStopConsumerException;
use AppBundle\RabbitMq\Exception\RejectStopConsumerException;
use AppBundle\Service\Options;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\Exception\StopConsumerException;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use RabbitMqBundle\MessageInterface;

abstract class AbstractConsumer implements ConsumerInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Options
     */
    protected $options;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        Options $options
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->options = $options;
    }

    abstract protected function getMessageClass(): string;

    abstract protected function executeBody(array $data): int;

    public function execute(AMQPMessage $message): int
    {
        // This fixes rare problem with lots of messages consumed at once. No idea why it helps though. :)
        // The problem can be reproduced when generating invoices for 1000+ clients.
        // Random email logs were showing as still in queue even though they were correctly sent and consumed.
        usleep(100000);

        try {
            $this->entityManager->clear();
            $this->options->refresh();

            return $this->executeBody($this->getData($message));
        } catch (InvalidMessageException $exception) {
            $this->logger->error($exception->getMessage());

            return self::MSG_REJECT;
        } catch (ConnectionException $exception) {
            $this->logger->error('Database connection failed.');

            throw new RejectRequeueStopConsumerException();
        } catch (\Throwable $exception) {
            if ($exception instanceof StopConsumerException) {
                throw $exception;
            }

            $this->logger->error($exception->getMessage());

            throw new RejectStopConsumerException();
        }
    }

    /**
     * @return mixed[]
     *
     * @throws InvalidMessageException
     */
    private function getData(AMQPMessage $message): array
    {
        try {
            $data = Json::decode($message->getBody(), Json::FORCE_ARRAY);
        } catch (JsonException $exception) {
            throw new InvalidMessageException(
                sprintf('Message is not valid JSON. Error: %s', $exception->getMessage())
            );
        }

        $this->validateMessageProperties($data);

        return $data;
    }

    /**
     * @param mixed[] $data
     *
     * @throws InvalidMessageException
     */
    private function validateMessageProperties(array $data): void
    {
        $reflection = new \ReflectionClass($this->getMessageClass());
        $message = $reflection->newInstanceWithoutConstructor();
        assert($message instanceof MessageInterface);

        $this->validateProperties($message->getBodyProperties(), $data);
    }

    /**
     * @throws InvalidMessageException
     */
    private function validateProperties(array $properties, array $data): void
    {
        foreach ($properties as $key => $property) {
            $isArray = is_array($property);

            $this->validateProperty(
                $isArray ? $key : $property,
                $data
            );

            if ($isArray) {
                $this->validateProperties($property, $data[$key]);
            }
        }
    }

    /**
     * @throws InvalidMessageException
     */
    private function validateProperty(string $key, array $data): void
    {
        if (array_key_exists($key, $data)) {
            return;
        }

        throw new InvalidMessageException(
            sprintf(
                'Invalid "%s" message, missing property "%s".',
                $this->getMessageClass(),
                $key
            )
        );
    }
}
