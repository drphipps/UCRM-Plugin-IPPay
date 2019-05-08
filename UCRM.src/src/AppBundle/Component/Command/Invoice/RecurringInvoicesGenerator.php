<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Command\Invoice;

use AppBundle\DataProvider\ClientDataProvider;
use AppBundle\Entity\DraftGeneration;
use AppBundle\Entity\Option;
use AppBundle\Facade\DraftGenerationFacade;
use AppBundle\RabbitMq\Invoice\GenerateDraftsMessage;
use AppBundle\Service\Options;
use Psr\Log\LoggerInterface;
use RabbitMqBundle\RabbitMqEnqueuer;

class RecurringInvoicesGenerator
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var ClientDataProvider
     */
    private $clientDataProvider;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var DraftGenerationFacade
     */
    private $draftGenerationFacade;

    public function __construct(
        LoggerInterface $logger,
        Options $options,
        ClientDataProvider $clientDataProvider,
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        DraftGenerationFacade $draftGenerationFacade
    ) {
        $this->logger = $logger;
        $this->options = $options;
        $this->clientDataProvider = $clientDataProvider;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->draftGenerationFacade = $draftGenerationFacade;
    }

    public function generate(\DateTimeImmutable $nextInvoicingDay, bool $force, bool $sendNotification): void
    {
        if (! $force && $this->options->get(Option::INVOICE_TIME_HOUR) !== (int) date('G')) {
            return;
        }

        $this->logger->info(
            sprintf(
                'Searching for clients for invoice generation (invoicing day "%s").',
                $nextInvoicingDay->format('Y-m-d')
            )
        );

        $clients = $this->clientDataProvider->getClientsForRecurringInvoicesGeneration($nextInvoicingDay);
        $draftGeneration = new DraftGeneration();
        $draftGeneration->setCount(count($clients));
        $draftGeneration->setSendNotification($sendNotification);

        if ($draftGeneration->getCount() > 0) {
            // must be before enqueue, otherwise rabbit will start generating drafts and failing,
            // because it's not yet in database
            $this->draftGenerationFacade->handleNew($draftGeneration);

            foreach ($clients as $client) {
                $this->rabbitMqEnqueuer->enqueue(
                    new GenerateDraftsMessage($draftGeneration->getUuid(), $client, $nextInvoicingDay)
                );
            }

            $this->logger->info(sprintf('Enqueued %d clients for invoice generation.', $draftGeneration->getCount()));
        } else {
            $this->logger->info('Did not find any applicable clients for invoice generation.');
        }
    }
}
