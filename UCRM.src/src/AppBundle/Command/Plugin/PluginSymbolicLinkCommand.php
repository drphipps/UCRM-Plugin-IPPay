<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Plugin;

use AppBundle\Facade\PluginFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginSymbolicLinkCommand extends Command
{
    /**
     * @var PluginFacade
     */
    private $pluginFacade;

    public function __construct(PluginFacade $pluginFacade)
    {
        $this->pluginFacade = $pluginFacade;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:plugin:symlink');
        $this->setDescription('Re/generates symlinks for plugin public scripts.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->pluginFacade->regenerateSymlinks();

        return 0;
    }
}
