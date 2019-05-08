<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Demo;

use AppBundle\Component\Demo\DemoDataShifter;
use AppBundle\Util\DateTimeImmutableFactory;
use Nette\Utils\Strings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DemoShiftCommand extends Command
{
    /**
     * @var DemoDataShifter
     */
    private $demoDataShifter;

    public function __construct(DemoDataShifter $demoDataShifter)
    {
        $this->demoDataShifter = $demoDataShifter;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:demo:shift');
        $this->addOption(
            'date',
            null,
            InputOption::VALUE_OPTIONAL,
            'Shift using this date as "now".'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $date */
        $date = $input->getOption('date');
        if (Strings::match($date ?? '', '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/')) {
            $date = DateTimeImmutableFactory::createDate($date);
        } else {
            $date = null;
        }

        $this->demoDataShifter->shift($date);

        return 0;
    }
}
