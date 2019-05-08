<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Component\Csv\EntityCsvFactory\ClientLogsViewCsvFactory;
use AppBundle\Entity\Download;
use AppBundle\Entity\User;
use AppBundle\Factory\ClientLogsViewPdfFactory;
use AppBundle\RabbitMq\ClientLogsView\ExportClientLogsViewMessage;
use AppBundle\Service\ClientLogsView\ClientLogsViewConverter;
use AppBundle\Service\DownloadFinisher;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;
use RabbitMqBundle\RabbitMqEnqueuer;

class ClientLogsViewFacade
{
    /**
     * @var ClientLogsViewConverter
     */
    private $clientLogsConverter;

    /**
     * @var ClientLogsViewCsvFactory
     */
    private $clientLogsViewCsvFactory;

    /**
     * @var ClientLogsViewPdfFactory
     */
    private $clientLogsViewPdfFactory;

    /**
     * @var DownloadFinisher
     */
    private $downloadFinisher;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    public function __construct(
        ClientLogsViewConverter $clientLogsConverter,
        ClientLogsViewCsvFactory $clientLogsViewCsvFactory,
        ClientLogsViewPdfFactory $clientLogsViewPdfFactory,
        DownloadFinisher $downloadFinisher,
        EntityManager $em,
        Options $options,
        RabbitMqEnqueuer $rabbitMqEnqueuer
    ) {
        $this->clientLogsConverter = $clientLogsConverter;
        $this->clientLogsViewCsvFactory = $clientLogsViewCsvFactory;
        $this->clientLogsViewPdfFactory = $clientLogsViewPdfFactory;
        $this->downloadFinisher = $downloadFinisher;
        $this->em = $em;
        $this->options = $options;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
    }

    public function finishCsvExport(int $downloadId, array $clientLogsViewIds): bool
    {
        return $this->downloadFinisher->finishDownload(
            $downloadId,
            'export.csv',
            function () use ($clientLogsViewIds) {
                return $this->clientLogsViewCsvFactory->create($clientLogsViewIds);
            }
        );
    }

    public function finishPdfExport(int $downloadId, array $clientLogsViewIds): bool
    {
        return $this->downloadFinisher->finishDownload(
            $downloadId,
            'export.pdf',
            function () use ($clientLogsViewIds) {
                return $this->clientLogsViewPdfFactory->create($clientLogsViewIds);
            }
        );
    }

    public function prepareExport(string $name, array $ids, User $user, string $fileType): void
    {
        $download = new Download();

        $this->em->transactional(
            function () use ($download, $name, $user) {
                $download->setName($name);
                $download->setCreated(new \DateTime());
                $download->setStatus(Download::STATUS_PENDING);
                $download->setUser($user);

                $this->em->persist($download);
            }
        );

        $this->rabbitMqEnqueuer->enqueue(new ExportClientLogsViewMessage($download, $ids, $fileType));
    }
}
