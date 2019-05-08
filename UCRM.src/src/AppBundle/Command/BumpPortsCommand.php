<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Component\Command\Ports\PortsBump;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BumpPortsCommand extends Command
{
    /**
     * @var PortsBump
     */
    private $portsBump;

    public function __construct(PortsBump $portsBump)
    {
        $this->portsBump = $portsBump;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:ports:bump')->setDescription('CRM ports bump.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->portsBump->update();

        return 0;
    }
}
