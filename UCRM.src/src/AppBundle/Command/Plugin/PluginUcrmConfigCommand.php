<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Plugin;

use AppBundle\Facade\PluginUcrmConfigFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginUcrmConfigCommand extends Command
{
    /**
     * @var PluginUcrmConfigFacade
     */
    private $configFacade;

    public function __construct(PluginUcrmConfigFacade $configFacade)
    {
        $this->configFacade = $configFacade;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:plugin:ucrm-config');
        $this->setDescription('Re/generates ucrm.json files for all plugins.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->configFacade->regenerateUcrmConfigs();

        return 0;
    }
}
