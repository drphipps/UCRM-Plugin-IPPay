<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Component\Command\Wizard\InitiateWizardUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitiateWizardUserCommand extends Command
{
    /**
     * @var InitiateWizardUser
     */
    private $initiateWizardUser;

    public function __construct(InitiateWizardUser $initiateWizardUser)
    {
        $this->initiateWizardUser = $initiateWizardUser;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:wizard:init');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initiateWizardUser->init();

        return 0;
    }
}
