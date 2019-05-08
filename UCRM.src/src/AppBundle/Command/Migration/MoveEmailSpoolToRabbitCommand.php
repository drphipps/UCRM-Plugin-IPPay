<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Migration;

use AppBundle\Service\Email\EmailEnqueuer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Introduced in version 2.5.0 to prevent loss of data in email spool.
 *
 * @todo Can be safely deleted in the future when everyone is on 2.5.0.
 */
class MoveEmailSpoolToRabbitCommand extends Command
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var EmailEnqueuer
     */
    private $emailEnqueuer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(string $rootDir, EmailEnqueuer $emailEnqueuer, LoggerInterface $logger)
    {
        $this->rootDir = $rootDir;
        $this->emailEnqueuer = $emailEnqueuer;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:migration:moveEmailSpoolToRabbit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $swiftMailerSpoolPath = sprintf('%s/EmailQueue/spool', $this->rootDir);
        $fs = new Filesystem();
        if (! $fs->exists($swiftMailerSpoolPath)) {
            return 0;
        }

        $finder = new Finder();
        $finder->files()->in($swiftMailerSpoolPath);

        foreach ($finder as $file) {
            try {
                $message = unserialize($file->getContents());
                if ($message instanceof \Swift_Message) {
                    $this->emailEnqueuer->enqueue($message, EmailEnqueuer::PRIORITY_LOW);
                    $this->logger->info(sprintf('Enqueued message "%s" to RabbitMQ.', $file->getFilename()));
                }
                $fs->remove($file->getPathname());
                $this->logger->info(sprintf('Removed file "%s" from spool.', $file->getFilename()));
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        return 0;
    }
}
