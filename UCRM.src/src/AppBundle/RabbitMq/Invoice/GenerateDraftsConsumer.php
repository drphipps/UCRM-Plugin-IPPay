<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\RabbitMq\Invoice;

use AppBundle\Entity\Client;
use AppBundle\Entity\DraftGeneration;
use AppBundle\Entity\DraftGenerationItem;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Facade\DraftGenerationFacade;
use AppBundle\Facade\RecurringInvoicesFacade;
use AppBundle\RabbitMq\AbstractConsumer;
use AppBundle\Service\Options;
use AppBundle\Util\DateTimeImmutableFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class GenerateDraftsConsumer extends AbstractConsumer
{
    /**
     * @var RecurringInvoicesFacade
     */
    private $recurringInvoicesFacade;

    /**
     * @var DraftGenerationFacade
     */
    private $draftGenerationFacade;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        Options $options,
        RecurringInvoicesFacade $recurringInvoicesFacade,
        DraftGenerationFacade $draftGenerationFacade
    ) {
        parent::__construct($entityManager, $logger, $options);

        $this->recurringInvoicesFacade = $recurringInvoicesFacade;
        $this->draftGenerationFacade = $draftGenerationFacade;
    }

    protected function getMessageClass(): string
    {
        return GenerateDraftsMessage::class;
    }

    public function executeBody(array $data): int
    {
        $draftGeneration = $this->entityManager->getRepository(DraftGeneration::class)->findOneBy(
            [
                'uuid' => $data['draftGenerationUUID'],
            ]
        );
        if (! $draftGeneration) {
            $this->logger->warning(sprintf('Draft generation UUID %s not found.', $data['draftGenerationUUID']));

            return self::MSG_REJECT;
        }

        $client = $this->entityManager->find(Client::class, $data['clientId']);
        if (! $client) {
            $this->logger->warning(sprintf('Client ID %d not found.', $data['clientId']));

            return self::MSG_REJECT;
        }

        try {
            $nextInvoicingDay = DateTimeImmutableFactory::createFromFormat(
                \DateTimeImmutable::ATOM,
                $data['nextInvoicingDay']
            );
        } catch (\InvalidArgumentException $exception) {
            $this->logger->warning(sprintf('Next invoicing day is invalid: %s', $exception->getMessage()));

            return self::MSG_REJECT;
        }

        $countCreatedDrafts = 0;
        $countApprovedDrafts = 0;
        do {
            [$createdDrafts, $approvedDrafts] = $this->recurringInvoicesFacade->processServices(
                $client,
                $nextInvoicingDay
            );

            foreach ($createdDrafts as $invoice) {
                $this->createDraftGenerationItem($draftGeneration, $invoice, true);
                ++$countCreatedDrafts;
            }

            foreach ($approvedDrafts as $invoice) {
                $this->createDraftGenerationItem($draftGeneration, $invoice, false);
                ++$countApprovedDrafts;
            }
        } while ($createdDrafts || $approvedDrafts);

        if ($countCreatedDrafts + $countApprovedDrafts > 0) {
            $draftGeneration->setCountSuccess($draftGeneration->getCountSuccess() + 1);
        } else {
            $draftGeneration->setCountFailure($draftGeneration->getCountFailure() + 1);
        }

        $this->draftGenerationFacade->handleEdit($draftGeneration);

        $this->logger->info(
            sprintf(
                'Created %d drafts, of that %d will be automatically approved.',
                $countCreatedDrafts + $countApprovedDrafts,
                $countApprovedDrafts
            )
        );

        return self::MSG_ACK;
    }

    private function createDraftGenerationItem(DraftGeneration $draftGeneration, Invoice $invoice, bool $draft): void
    {
        $item = new DraftGenerationItem();
        $item->setDraftGeneration($draftGeneration);
        $item->setInvoice($invoice);
        $item->setDraft($draft);
        $draftGeneration->addItem($item);
    }
}
