<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Command;

use AppBundle\Service\Encryption;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\TranslatorInterface;

trait BaseCommandTrait
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var TranslatorInterface
     */
    public $translator;

    /**
     * @var Options
     */
    protected $options;

    /**
     * @var Encryption
     */
    protected $encryption;

    /**
     * @var string
     */
    protected $rootDir;

    protected function init()
    {
        $this->container = $this->getContainer();

        $this->logger = $this->container->get('logger');
        $this->em = $this->container->get('doctrine.orm.default_entity_manager');
        $this->translator = $this->container->get(TranslatorInterface::class);
        $this->options = $this->container->get(Options::class);
        $this->rootDir = $this->container->get(KernelInterface::class)->getRootDir();
        $this->encryption = $this->container->get(Encryption::class);
    }

    private function getRunningCommandList(string $command): array
    {
        $command = sprintf('[%s]%s', substr($command, 0, 1), substr($command, 1));

        $cmd = sprintf('ps aux | grep %s', $command);
        exec($cmd, $runningList);

        return $runningList;
    }

    /**
     * @return bool
     */
    private function isCommandRunning(array $runningProcesses, int $deviceId, string $command)
    {
        $needle = sprintf(
            'php %s/console %s %d',
            $this->rootDir,
            $command,
            $deviceId
        );

        foreach ($runningProcesses as $processRow) {
            if (strpos($processRow, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
