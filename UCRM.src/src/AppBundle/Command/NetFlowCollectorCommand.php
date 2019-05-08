<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Component\NetFlow\EventLoopFactory;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NetFlowCollectorCommand extends Command
{
    /**
     * @var EventLoopFactory
     */
    private $eventLoopFactory;

    public function __construct(EventLoopFactory $eventLoopFactory)
    {
        $this->eventLoopFactory = $eventLoopFactory;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:netflow:collector')
            ->setDescription('Collects NetFlow data.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var LoopInterface $loop */
        $loop = $this->eventLoopFactory->create();

        $loop->run();

        return 0;
    }
}
