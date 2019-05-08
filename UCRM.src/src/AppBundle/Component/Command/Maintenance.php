<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Command;

use AppBundle\Component\QoS\CommandLogger;
use AppBundle\Database\UtcDateTimeType;
use AppBundle\Entity\DeviceLog;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\HeaderNotification;
use AppBundle\Entity\Option;
use AppBundle\Entity\PaymentToken;
use AppBundle\Entity\ServiceDeviceLog;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;
use Nette\Utils\Strings;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Maintenance
{
    private const QOS_LOGS_LIFETIME_MONTHS = 2;
    private const POSTGRES_MAX_INTERVAL_DAYS = 365000;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $logDir;

    /**
     * @var string
     */
    private $backupDir;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        string $environment,
        string $logDir,
        string $backupDir,
        EntityManager $em,
        LoggerInterface $logger,
        Options $options,
        Filesystem $filesystem
    ) {
        $this->environment = $environment;
        $this->logDir = $logDir;
        $this->backupDir = $backupDir;
        $this->em = $em;
        $this->logger = $logger;
        $this->options = $options;
        $this->filesystem = $filesystem;
    }

    public function runLogsCleanup(): int
    {
        $count = 0;
        $count += $this->deleteLogs(
            DeviceLog::class,
            $this->options->get(Option::LOG_LIFETIME_DEVICE),
            true
        );
        $count += $this->deleteLogs(
            ServiceDeviceLog::class,
            $this->options->get(Option::LOG_LIFETIME_SERVICE_DEVICE),
            true
        );
        $count += $this->deleteLogs(
            EmailLog::class,
            $this->options->get(Option::LOG_LIFETIME_EMAIL),
            true
        );
        $count += $this->deleteLogs(
            EntityLog::class,
            $this->options->get(Option::LOG_LIFETIME_ENTITY),
            true
        );
        $count += $this->deleteLogs(
            HeaderNotification::class,
            $this->options->get(Option::HEADER_NOTIFICATIONS_LIFETIME),
            true
        );

        return $count;
    }

    public function run(): int
    {
        $this->logger->info('Deleting old log entries.');

        $count = 0;
        $count += $this->deleteLogs(
            DeviceLog::class,
            $this->options->get(Option::LOG_LIFETIME_DEVICE)
        );
        $count += $this->deleteLogs(
            ServiceDeviceLog::class,
            $this->options->get(Option::LOG_LIFETIME_SERVICE_DEVICE)
        );
        $count += $this->deleteLogs(
            EmailLog::class,
            $this->options->get(Option::LOG_LIFETIME_EMAIL)
        );
        $count += $this->deleteLogs(
            EntityLog::class,
            $this->options->get(Option::LOG_LIFETIME_ENTITY)
        );
        $count += $this->deleteLogs(
            HeaderNotification::class,
            $this->options->get(Option::HEADER_NOTIFICATIONS_LIFETIME)
        );

        $count += $this->deletePaymentTokens();

        $this->logger->info(
            sprintf(
                'System deleted %d entries.',
                $count
            )
        );

        $this->logger->info('Deleting old log files.');
        $countLogFiles = $this->deleteLogFiles($this->logDir . CommandLogger::LOG_PATH);
        $this->logger->info(
            sprintf(
                'System deleted %d log files.',
                $countLogFiles
            )
        );

        return $count + $countLogFiles;
    }

    private function deleteLogs(string $entity, int $days, bool $runVacuum = false): int
    {
        $deleted = $this->em
            ->createQueryBuilder()
            ->delete($entity, 'l')
            ->where('DATE_ADD(l.createdDate, :days, \'DAY\') <= :now')
            ->setParameter('days', min($days, self::POSTGRES_MAX_INTERVAL_DAYS))
            ->setParameter('now', new \DateTime(), UtcDateTimeType::NAME)
            ->getQuery()
            ->execute();

        if ($runVacuum && $deleted > 0) {
            $this->em->getConnection()->executeUpdate(
                sprintf(
                    'VACUUM ANALYZE %s',
                    $this->em->getClassMetadata($entity)->getTableName()
                )
            );
        }

        return $deleted;
    }

    private function deletePaymentTokens(): int
    {
        $expr = $this->em->getExpressionBuilder();

        $subQuery = $this->em
            ->createQueryBuilder()
            ->select('i')
            ->from(Invoice::class, 'i')
            ->where($expr->in('i.invoiceStatus', ':statuses'))
            ->getDQL();

        return $this->em
            ->createQueryBuilder()
            ->delete(PaymentToken::class, 'pt')
            ->where($expr->notIn('pt.invoice', $subQuery))
            ->setParameter('statuses', Invoice::UNPAID_STATUSES)
            ->getQuery()
            ->execute();
    }

    private function deleteLogFiles(string $path): int
    {
        if (! $this->filesystem->exists($path)) {
            return 0;
        }

        $finder = new Finder();
        $finder->files();
        $finder->in($path);
        $finder->depth('== 0');

        $filesToDelete = [];
        foreach ($finder as $file) {
            if ($this->shouldDeleteLogFile($file)) {
                $filesToDelete[] = $file;
            }
        }

        if ($filesToDelete) {
            $this->filesystem->remove($filesToDelete);
        }

        return count($filesToDelete);
    }

    private function shouldDeleteLogFile(SplFileInfo $file): bool
    {
        $date = new \DateTime(sprintf('-%d months', self::QOS_LOGS_LIFETIME_MONTHS));
        $matches = Strings::match(
            $file->getFilename(),
            sprintf('/^%s(\d{6})\.log$/', preg_quote($this->environment))
        );

        return $matches && $date->format('Ym') >= $matches[1];
    }
}
