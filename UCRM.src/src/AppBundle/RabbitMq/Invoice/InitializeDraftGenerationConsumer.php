<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Invoice;

use AppBundle\Component\Command\Invoice\RecurringInvoicesGenerator;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use AppBundle\Util\DateTimeImmutableFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class InitializeDraftGenerationConsumer extends AbstractConsumer
{
    /**
     * @var RecurringInvoicesGenerator
     */
    private $recurringInvoicesGenerator;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        Options $options,
        RecurringInvoicesGenerator $recurringInvoicesGenerator
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->recurringInvoicesGenerator = $recurringInvoicesGenerator;
    }

    protected function getMessageClass(): string
    {
        return InitializeDraftGenerationMessage::class;
    }

    public function executeBody(array $data): int
    {
        try {
            $nextInvoicingDay = DateTimeImmutableFactory::createFromFormat(
                \DateTimeImmutable::ATOM,
                $data['nextInvoicingDay']
            );
        } catch (\InvalidArgumentException $exception) {
            $this->logger->warning(sprintf('Next invoicing day is invalid: %s', $exception->getMessage()));

            return self::MSG_REJECT;
        }

        $this->recurringInvoicesGenerator->generate(
            $nextInvoicingDay,
            true,
            (bool) $data['sendNotificationOnFinish']
        );

        return self::MSG_ACK;
    }
}
