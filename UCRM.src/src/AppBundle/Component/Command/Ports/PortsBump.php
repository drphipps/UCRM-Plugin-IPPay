<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Component\Command\Ports;

use AppBundle\Entity\Option;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class PortsBump
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(EntityManager $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function update(): void
    {
        $saveServerPort = $this->setNewPort(Option::SERVER_PORT);
        $saveServerSuspendPort = $this->setNewPort(Option::SERVER_SUSPEND_PORT);

        if ($saveServerPort || $saveServerSuspendPort) {
            $this->em->flush();
        }
    }

    private function setNewPort(string $name): bool
    {
        $callFlush = false;
        $port = $this->getPort($name);

        if ($port > 0) {
            $portDb = $this->em->getRepository(Option::class)->findOneBy(
                [
                    'code' => $name,
                ]
            );

            if ($portDb && (null === $portDb->getValue() || 0 == $portDb->getValue())) {
                $portDb->setValue((string) $port);

                $msg = 'Setting %d as new %s to database.';
                $log = sprintf($msg, $port, $name);
                $callFlush = true;
            } else {
                $msg = '%s was not changed.';
                $log = sprintf($msg, $name);
            }
        } else {
            $msg = '%s is not in range 1-65535.';
            $log = sprintf($msg, $name);
        }

        $this->logger->info($log);

        return $callFlush;
    }

    private function getPort(string $name): int
    {
        $port = getenv($name);
        if (false === $port) {
            return 0;
        }

        $port = (int) $port;
        if ($port > 0 && $port <= 65535) {
            return $port;
        }

        return 0;
    }
}
