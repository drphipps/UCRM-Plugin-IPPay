<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Suspension;

use AppBundle\FileManager\SuspensionFileManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupStaticSuspensionPageCommand extends Command
{
    /**
     * @var SuspensionFileManager
     */
    private $suspensionFileManager;

    public function __construct(SuspensionFileManager $suspensionFileManager)
    {
        $this->suspensionFileManager = $suspensionFileManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:suspension:setupStaticSuspensionPage')
            ->setDescription('Generates static suspension page if needed.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->suspensionFileManager->regenerateSuspensionFile();

        return 0;
    }
}
