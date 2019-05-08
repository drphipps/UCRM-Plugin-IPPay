<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Component\NetFlow;

use Psr\Log\LoggerInterface;
use React\Datagram\Factory as SocketFactory;
use React\Datagram\Socket;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

class EventLoopFactory
{
    /**
     * @var string
     */
    private $netflowHost;

    /**
     * @var int
     */
    private $netflowPort;

    /**
     * @var int
     */
    private $netflowReloadPeriod;

    /**
     * @var int
     */
    private $flushPeriod;

    /**
     * @var IpChecker
     */
    private $ipChecker;

    /**
     * @var Collector
     */
    private $collector;

    /**
     * @var PacketParser
     */
    private $packetParser;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        string $netflowHost,
        int $netflowPort,
        int $netflowReloadPeriod,
        int $flushPeriod,
        IpChecker $ipChecker,
        Collector $collector,
        PacketParser $packetParser,
        LoggerInterface $logger
    ) {
        $this->netflowHost = $netflowHost;
        $this->netflowPort = $netflowPort;
        $this->netflowReloadPeriod = $netflowReloadPeriod;
        $this->flushPeriod = $flushPeriod;
        $this->ipChecker = $ipChecker;
        $this->collector = $collector;
        $this->packetParser = $packetParser;
        $this->logger = $logger;
    }

    public function create(): LoopInterface
    {
        $loop = Factory::create();

        $loop->futureTick($this->ipChecker);
        $loop->addPeriodicTimer($this->netflowReloadPeriod, $this->ipChecker);
        $loop->addPeriodicTimer(
            $this->flushPeriod,
            function () {
                $this->collector->flush();
            }
        );

        $factory = new SocketFactory($loop);
        $factory
            ->createServer(sprintf('udp://%s:%s', $this->netflowHost, $this->netflowPort))
            ->then(
                function (Socket $server) {
                    $server->on(
                        'message',
                        function (string $packet, string $peer) {
                            try {
                                $this->collector->collect($this->packetParser->parse($packet, $peer));
                            } catch (ParseErrorException $e) {
                                $this->logger->error(sprintf('NetFlow parse error: %s', $e->getMessage()));
                            }
                        }
                    );
                }
            );

        return $loop;
    }
}
