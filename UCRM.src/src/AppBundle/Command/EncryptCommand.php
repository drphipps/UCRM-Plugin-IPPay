<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Command;

use AppBundle\Component\Command\Password\Encrypt;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EncryptCommand extends Command
{
    /**
     * @var Encrypt
     */
    private $encrypt;

    public function __construct(Encrypt $encrypt)
    {
        $this->encrypt = $encrypt;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:encrypt')
            ->setDescription('Encrypts passwords in database if not already encrypted.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->encrypt->encrypt();

        return 0;
    }
}
