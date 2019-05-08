<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Migration;

use AppBundle\Entity\InvoiceApproveDraft;
use AppBundle\Entity\InvoiceSendEmail;
use AppBundle\Entity\PaymentPlanUnsubscribe;
use AppBundle\RabbitMq\Invoice\ApproveDraftMessage;
use AppBundle\RabbitMq\Invoice\SendInvoiceMessage;
use AppBundle\RabbitMq\PaymentPlan\CancelPaymentPlanMessage;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use RabbitMqBundle\RabbitMqEnqueuer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Introduced in version 2.3.0 to prevent loss of data in "approve draft" and "send invoice email" queues.
 * Extended in version 2.11.0 to prevent loss of data in "cancel subscription" queue.
 *
 * @todo "approve draft" and "send invoice email" can be safely deleted in the future when everyone is on 2.3.0.
 * @todo "cancel subscription" can be safely deleted in the future when everyone is on 2.11.0.
 * @todo InvoiceApproveDraft, InvoiceSendEmail and PaymentPlanUnsubscribe entities should be deleted as well.
 * @todo Make sure to include IF EXISTS to drop migration SQL when doing this.
 *       We did it wrong and actually released the DROP migrations and then removed them (applies to 2.3.0 entities only).
 */
class MoveQueuesToRabbitCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RabbitMqEnqueuer
     */
    private $enqueuer;

    public function __construct(EntityManagerInterface $entityManager, RabbitMqEnqueuer $enqueuer)
    {
        $this->entityManager = $entityManager;
        $this->enqueuer = $enqueuer;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:migration:moveQueuesToRabbit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            /** @var InvoiceSendEmail[] $sendInvoices */
            $sendInvoices = $this->entityManager->getRepository(InvoiceSendEmail::class)->findAll();
            foreach ($sendInvoices as $sendInvoice) {
                $this->enqueuer->enqueue(new SendInvoiceMessage($sendInvoice->getInvoice()));
                $this->entityManager->remove($sendInvoice);
            }
        } catch (TableNotFoundException $exception) {
        }

        try {
            /** @var InvoiceApproveDraft[] $approveDrafts */
            $approveDrafts = $this->entityManager->getRepository(InvoiceApproveDraft::class)->findAll();
            foreach ($approveDrafts as $approveDraft) {
                $this->enqueuer->enqueue(new ApproveDraftMessage($approveDraft->getInvoice()));
                $this->entityManager->remove($approveDraft);
            }
        } catch (TableNotFoundException $exception) {
        }

        try {
            /** @var PaymentPlanUnsubscribe[] $unsubscribe */
            $unsubscribe = $this->entityManager->getRepository(PaymentPlanUnsubscribe::class)->findAll();
            foreach ($unsubscribe as $item) {
                $this->enqueuer->enqueue(new CancelPaymentPlanMessage($item->getPaymentPlan()));
                $this->entityManager->remove($item);
            }
        } catch (TableNotFoundException $exception) {
        }

        $this->entityManager->flush();

        return 0;
    }
}
