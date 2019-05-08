<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Development;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class RemoveYamlDuplicatesCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crm:yaml:duplicate')
            ->setDescription('Removes duplicate rows with equal key and value from translation yaml file.')
            ->addUsage('< path/to/input.yml')
            ->addOption('info', 'i', InputOption::VALUE_NONE, 'Displays info instead of new yaml.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (0 === ftell(STDIN)) {
            $contents = '';
            while (! feof(STDIN)) {
                $contents .= fread(STDIN, 1024);
            }
        } else {
            $output->writeln(
                'No input given to STDIN. Use <info>console crm:yaml:duplicate < path/to/input.yml</info>'
            );

            return 1;
        }

        $yaml = Yaml::parse($contents);
        $countOriginal = count($yaml);
        $duplicates = [];
        foreach ($yaml as $key => $value) {
            if ($key === $value) {
                $duplicates[] = $value;
                unset($yaml[$key]);
            }
        }
        $countFixed = count($yaml);

        if ($input->getOption('info')) {
            $duplicateList = [];
            foreach ($duplicates as $duplicate) {
                $duplicateList[] = sprintf('    <comment>%s</comment>', $duplicate);
            }
            $output->writeln(
                array_merge(
                    [
                        sprintf('Original yaml line count: <info>%s</info>', $countOriginal),
                        sprintf('Fixed yaml line count: <info>%s</info>', $countFixed),
                        'Duplicate list:',
                    ],
                    $duplicateList
                )
            );
        } else {
            $output->write(
                Yaml::dump($yaml),
                true,
                OutputInterface::OUTPUT_RAW
            );
        }

        return 0;
    }
}
