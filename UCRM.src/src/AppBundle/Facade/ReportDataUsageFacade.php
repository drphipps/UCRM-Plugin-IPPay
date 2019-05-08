<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Entity\ReportDataUsage;
use AppBundle\Entity\Service;
use AppBundle\Entity\User;
use AppBundle\Event\Report\ReportGeneratedEvent;
use AppBundle\Factory\ReportDataUsageFactory;
use AppBundle\RabbitMq\Report\ReportDataUsageMessage;
use Doctrine\ORM\EntityManagerInterface;
use RabbitMqBundle\RabbitMqEnqueuer;
use TransactionEventsBundle\TransactionDispatcher;

class ReportDataUsageFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    /**
     * @var ReportDataUsageFactory
     */
    private $reportDataUsageFactory;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        RabbitMqEnqueuer $rabbitMqEnqueuer,
        ReportDataUsageFactory $reportDataUsageFactory,
        TransactionDispatcher $transactionDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
        $this->reportDataUsageFactory = $reportDataUsageFactory;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function enqueueMessage(User $user): void
    {
        $this->rabbitMqEnqueuer->enqueue(new ReportDataUsageMessage($user));
    }

    public function generateReportData(?int $userId = null): bool
    {
        $this->transactionDispatcher->transactional(
            function (EntityManagerInterface $entityManager) use ($userId) {
                $this->handleDeleteAll();

                foreach ($this->entityManager->getRepository(Service::class)->getActiveServicesWithActiveInPast() as $service) {
                    if ($reportDataUsage = $this->reportDataUsageFactory->createByService($service)) {
                        $this->handleNew($reportDataUsage);
                    }
                }

                if ($userId) {
                    $user = $entityManager->find(User::class, $userId);
                    if ($user) {
                        yield new ReportGeneratedEvent($user);
                    }
                }
            }
        );

        return true;
    }

    public function handleNew(ReportDataUsage $reportDataUsage): void
    {
        $this->entityManager->persist($reportDataUsage);
        $this->entityManager->flush();
    }

    public function handleDeleteAll(): void
    {
        $this->entityManager->getConnection()->query('DELETE FROM report_data_usage');
    }

    public function isReportGenerated(): bool
    {
        return (bool) $this->entityManager->getRepository(ReportDataUsage::class)->findOneBy(
            [
                'reportCreated' => new \DateTime(),
            ]
        );
    }
}
