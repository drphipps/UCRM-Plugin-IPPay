<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Component\Command\Version\Bump;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BumpVersionCommand extends Command
{
    /**
     * @var Bump
     */
    private $bump;

    public function __construct(Bump $bump)
    {
        $this->bump = $bump;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:version:bump')
            ->setDescription('CRM version bump.')
            ->addArgument(
                'newVersion',
                InputArgument::OPTIONAL,
                'New version string e.g. "2.0.3".'
            )
            ->addOption(
                'save-to-database',
                null,
                InputOption::VALUE_NONE,
                'Save UCRM version into database.'
            )
            ->setDescription('Save UCRM version into yml or database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $newVersion = $input->getArgument('newVersion');
        $bumpService = $this->bump;

        if ($input->getOption('save-to-database')) {
            $bumpService->saveVersionToDatabase($newVersion);

            return 0;
        }
        if ($newVersion) {
            $bumpService->saveVersionToYml($newVersion);

            return 0;
        }

        return 1;
    }
}
