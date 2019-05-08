<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Development;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearDatabaseCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('crm:development:database:clear')
            ->setDescription('Drops the database and creates new empty database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->entityManager->getConnection();

        $database = $connection->getDatabase();
        $connectionParams = [
            'user' => $connection->getUsername(),
            'password' => $connection->getPassword(),
            'host' => $connection->getHost(),
            'driver' => $connection->getDriver()->getName(),
            'port' => $connection->getPort(),
        ];

        $connection->close();

        $connection = DriverManager::getConnection($connectionParams);
        $connection->exec(sprintf('DROP DATABASE IF EXISTS %s', $database));
        $connection->exec(sprintf('CREATE DATABASE %s', $database));

        $output->writeln(sprintf('Database %s was dropped and created.', $database));

        return 0;
    }
}
