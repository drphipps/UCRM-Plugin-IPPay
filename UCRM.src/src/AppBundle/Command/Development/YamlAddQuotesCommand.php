<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Development;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class YamlAddQuotesCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crm:yaml:addquotes')
            ->setDescription('Adds quotes to all keys and values if missing')
            ->addUsage('< path/to/input.yml');
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
        $output->write(
            Yaml::dump($yaml),
            true,
            OutputInterface::OUTPUT_RAW
        );

        return 0;
    }
}
