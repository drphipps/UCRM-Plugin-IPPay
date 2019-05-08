<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Doctrine;

use Doctrine\Bundle\MigrationsBundle\Command\DoctrineCommand;
use Doctrine\Bundle\MigrationsBundle\Command\Helper\DoctrineCommandHelper;
use Doctrine\Bundle\MigrationsBundle\Command\MigrationsDiffDoctrineCommand;
use Doctrine\DBAL\Migrations\Provider\OrmSchemaProvider;
use Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DoctrineMigrationsUpToDateCommand extends MigrationsDiffDoctrineCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('crm:migrations:up-to-date');
    }

    /**
     * Copy-pasted relevant code from original DiffCommand.
     *
     * @see DiffCommand::execute()
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $application = $this->getApplication();
        assert($application instanceof Application);
        DoctrineCommandHelper::setApplicationHelper($application, $input);

        $configuration = $this->getMigrationConfiguration($input, $output);
        DoctrineCommand::configureMigrations($application->getKernel()->getContainer(), $configuration);

        $configuration = $this->getMigrationConfiguration($input, $output);
        $connection = $configuration->getConnection();

        $fromSchema = $connection->getSchemaManager()->createSchema();
        $toSchema = (new OrmSchemaProvider($this->getHelper('entityManager')->getEntityManager()))->createSchema();

        if ($filterExpr = $connection->getConfiguration()->getFilterSchemaAssetsExpression()) {
            foreach ($toSchema->getTables() as $table) {
                $tableName = $table->getName();
                if (! preg_match($filterExpr, $this->resolveTableName($tableName))) {
                    $toSchema->dropTable($tableName);
                }
            }
        }

        $isUpToDate = true;
        $sql = $fromSchema->getMigrateToSql($toSchema, $connection->getDatabasePlatform());
        foreach ($sql as $query) {
            if (stripos($query, $configuration->getMigrationsTableName()) !== false) {
                continue;
            }

            $isUpToDate = false;
        }

        if ($isUpToDate) {
            $output->writeln('Doctrine migrations are up-to-date.');
        } else {
            $output->writeln('<error>Doctrine migrations are NOT up-to-date, run migrations diff.</error>');
        }

        return $isUpToDate ? 0 : 1;
    }

    private function resolveTableName($name): string
    {
        $pos = strpos($name, '.');

        return false === $pos ? $name : substr($name, $pos + 1);
    }
}
