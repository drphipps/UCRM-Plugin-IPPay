<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Component\Command\UAS\UasBump;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BumpUasCommand extends Command
{
    /**
     * @var UasBump
     */
    private $uasBump;

    public function __construct(UasBump $uasBump)
    {
        $this->uasBump = $uasBump;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:uas:bump')->setDescription('Save UAS info to database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->uasBump->update();

        return 0;
    }
}
