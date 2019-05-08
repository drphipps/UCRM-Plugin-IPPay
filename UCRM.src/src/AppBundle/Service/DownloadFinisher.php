<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Entity\Download;
use AppBundle\Event\Download\DownloadFinishedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Strings;
use Symfony\Component\Filesystem\Filesystem;
use TransactionEventsBundle\TransactionDispatcher;

class DownloadFinisher
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TransactionDispatcher
     */
    private $transactionDispatcher;

    public function __construct(string $rootDir, EntityManagerInterface $em, TransactionDispatcher $transactionDispatcher)
    {
        $this->rootDir = $rootDir;
        $this->em = $em;
        $this->transactionDispatcher = $transactionDispatcher;
    }

    public function finishDownload(
        int $downloadId,
        string $filename,
        callable $dataGenerator,
        bool $sendNotification = true
    ): bool {
        /** @var Download|null $download */
        $download = null;

        $this->transactionDispatcher->transactional(
            function () use ($downloadId, $filename, $dataGenerator, $sendNotification, &$download) {
                $download = $this->em->getRepository(Download::class)->find($downloadId);

                if (! $download) {
                    return;
                }
                try {
                    $data = $dataGenerator();
                } catch (\Throwable $e) {
                    $download->setStatus(Download::STATUS_FAILED);
                    $download->setStatusDescription(Strings::truncate($e->getMessage(), 250));

                    yield new DownloadFinishedEvent($download, $sendNotification);

                    return;
                }

                $now = new \DateTime();
                $filename = sprintf(
                    '%s%s.%s',
                    pathinfo($filename, PATHINFO_FILENAME),
                    $now->format('Y-m-d_His'),
                    pathinfo($filename, PATHINFO_EXTENSION)
                );

                $path = sprintf(
                    '/data/download/%d_%s',
                    $download->getId(),
                    rtrim($filename, '.')
                );
                $filesystem = new Filesystem();
                $filesystem->dumpFile($this->rootDir . $path, $data);

                $download->setPath($path);
                $download->setStatus(Download::STATUS_READY);
                $download->setGenerated($now);

                yield new DownloadFinishedEvent($download, $sendNotification);
            }
        );

        return $download && $download->getStatus() === Download::STATUS_READY;
    }
}
