<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Command\Development;

use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Strings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ConvertBackupToDemoMigrationCommand extends Command
{
    private const DEMO_MIGRATION_VERSION = '40180918160716';

    private const IGNORED_ROWS = [
        '"user"' => [
            1,
        ],
        'contact_type' => [
            1,
            2,
        ],
    ];

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(string $rootDir, EntityManagerInterface $entityManager)
    {
        $this->rootDir = $rootDir;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('crm:sql:convert')
            ->setDescription('Takes SQL from "dev/demo/convert.sh" and creates demo migration from it.')
            ->addUsage('< path/to/input.sql');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (0 === ftell(STDIN)) {
            $lines = file('php://stdin', FILE_SKIP_EMPTY_LINES);
        } else {
            $output->writeln(
                'No input given to STDIN. Use <info>console crm:sql:convert < path/to/input.sql</info>'
            );

            return 1;
        }

        $alreadyShiftedBy = 0;
        $data = $this->parseSql($lines, $alreadyShiftedBy);
        $up = $this->prepareUpMigration($data, $alreadyShiftedBy);
        $down = $this->prepareDownMigration($data);
        unset($lines);
        $migration = $this->prepareFullMigration($up, $down);
        unset($up);

        $migrationPath = sprintf(
            '%s/../docker/DoctrineMigrations/Version%s.php',
            $this->rootDir,
            self::DEMO_MIGRATION_VERSION
        );
        $fs = new Filesystem();
        $fs->dumpFile(
            $migrationPath,
            $migration
        );

        $output->writeln(
            sprintf(
                'Generated demo migration into <info>%s</info>',
                $migrationPath
            )
        );

        return 0;
    }

    private function prepareFullMigration(string $up, string $down): string
    {
        return strtr(
            $this->getMigrationTemplate(),
            [
                '<version>' => self::DEMO_MIGRATION_VERSION,
                '<up>' => $up,
                '<down>' => $down,
            ]
        );
    }

    private function prepareUpMigration(array $data, int $alreadyShiftedBy): string
    {
        $up = '';
        foreach ($data as $tableName => $tableData) {
            if (! ($tableData['values'] ?? null)) {
                continue;
            }

            $query = sprintf(
                "\n            INSERT INTO %s\n              %s\n            VALUES\n      %s",
                $tableName,
                $tableData['columns'],
                implode(",\n      ", $tableData['values'])
            );
            $up .= PHP_EOL . sprintf('        $this->addSql(\'%s\');', str_replace('\'', '\\\'', $query));
            unset($query);
        }
        unset($parsed);

        $extraQueries = [
            sprintf(
                'UPDATE "user" SET username = \'%s\', password = \'%s\', role = \'%s\' WHERE user_id = 1',
                User::USER_ADMIN,
                User::USER_ADMIN_PASSWORD,
                User::ROLE_SUPER_ADMIN
            ),
            sprintf('UPDATE "option" SET value = 1 WHERE code = \'%s\'', Option::SUSPEND_ENABLED),
            sprintf(
                'UPDATE "option" SET value = \'pk.eyJ1IjoidWJudC1wcmFndWUiLCJhIjoiY2lqazU3cmhmMDA0YnZsbHd3bGxrZnBxaSJ9.fSNSOzq6j8ugMkiFNXpZxg\' WHERE code = \'%s\'',
                Option::MAPBOX_TOKEN
            ),
            sprintf('UPDATE "option" SET value = NULL WHERE code = \'%s\'', Option::MAILER_PASSWORD),
            sprintf('UPDATE "option" SET value = 443 WHERE code = \'%s\'', Option::SERVER_PORT),
            sprintf('UPDATE "option" SET value = 81 WHERE code = \'%s\'', Option::SERVER_SUSPEND_PORT),
            sprintf('UPDATE "option" SET value = \'ucrm-demo.ui.com\' WHERE code = \'%s\'', Option::SERVER_FQDN),
            sprintf(
                'UPDATE "option" SET value = \'noreply@example.com\' WHERE code = \'%s\'',
                Option::MAILER_SENDER_ADDRESS
            ),
            sprintf('UPDATE "option" SET value = \'1\' WHERE code = \'%s\'', Option::SUBSCRIPTIONS_ENABLED_CUSTOM),
            sprintf('UPDATE "option" SET value = \'America/Chicago\' WHERE code = \'%s\'', Option::APP_TIMEZONE),
            sprintf('UPDATE "option" SET value = \'1\' WHERE code = \'%s\'', Option::INVOICE_PERIOD_START_DAY),
            sprintf('UPDATE "option" SET value = \'2\' WHERE code = \'%s\'', Option::INVOICING_PERIOD_TYPE),
            sprintf('UPDATE general SET value = \'1\' WHERE code = \'%s\'', General::INVOICE_TOTALS_MIGRATION_COMPLETE),
            sprintf('UPDATE general SET value = \'1\' WHERE code = \'%s\'', General::ONBOARDING_HOMEPAGE_FINISHED),
            sprintf('UPDATE general SET value = \'1\' WHERE code = \'%s\'', General::WIZARD_ACCOUNT_DONE),
            sprintf(
                'UPDATE general SET value = \'%d\' WHERE code = \'%s\'',
                $alreadyShiftedBy,
                General::DEMO_MIGRATION_SHIFT
            ),
            // unsuspend everything, correct data suspensions this is handled with commands after migration
            'UPDATE service SET reason_id = NULL, suspended_from = NULL',
        ];
        $extraQueries = array_merge($extraQueries, $this->getSequenceFixSql(array_keys($data)));
        $extraQueries = array_map(
            function ($query) {
                return sprintf('        $this->addSql(\'%s\');', str_replace('\'', '\\\'', $query));
            },
            $extraQueries
        );

        return $up . PHP_EOL . implode(PHP_EOL, $extraQueries);
    }

    private function prepareDownMigration(array $data): string
    {
        $down = '';
        foreach ($data as $tableName => $tableData) {
            if (! array_key_exists($tableName, self::IGNORED_ROWS)) {
                $down .= sprintf(
                    "                'TRUNCATE %s CASCADE;',\n",
                    $tableName
                );

                continue;
            }

            [, $primaryKey] = Strings::match(
                $tableData['columns'],
                '~^\(([\w]+),~'
            );

            $down .= sprintf(
                "                'DELETE FROM %s WHERE %s NOT IN (%s);',\n",
                $tableName,
                $primaryKey,
                implode(', ', self::IGNORED_ROWS[$tableName])
            );
        }

        return rtrim($down);
    }

    private function parseSql(array $lines, int &$alreadyShiftedBy): array
    {
        $parsed = [];
        foreach ($lines as $line) {
            [, $table, $columns, $values] = Strings::match(
                $line,
                '~INSERT INTO ([\w\"]+) (\(.+\)) VALUES (\(.+\))~'
            );
            if (! $table) {
                continue;
            }

            if (array_key_exists($table, self::IGNORED_ROWS)) {
                [, $primaryKeyValue] = Strings::match(
                    $values,
                    '~^\(([\d]+),~'
                );

                if (in_array((int) $primaryKeyValue, self::IGNORED_ROWS[$table], true)) {
                    // do not skip, we need it for down migration, just ignore the data
                    $values = null;
                }
            }

            if ($table === 'general') {
                if (Strings::contains($values, General::DEMO_MIGRATION_SHIFT)) {
                    // $values = (33, 'demo_migration_shift', '0')
                    $alreadyShiftedBy = (int) trim(explode(',', trim($values, '()'))[2], '\' ');
                }

                continue;
            }

            if (! array_key_exists($table, $parsed)) {
                $parsed[$table]['columns'] = $columns;
            }

            if ($values) {
                $parsed[$table]['values'][] = '        ' . $values;
            }
        }

        return $parsed;
    }

    /**
     * This is bit hacky, since it does not work with backup, but uses current database,
     * however it's by far the easiest option to handle this and it works.
     * It grabs all sequences from database along with their tables and columns
     * and then creates queries to set these sequences to max ID for them.
     */
    private function getSequenceFixSql(array $tableFilter): array
    {
        $queries = $this->entityManager
            ->getConnection()->fetchAll(
                <<<'EOT'
SELECT 
  'SELECT SETVAL(' ||
  quote_literal(table_name || '_' || column_name || '_seq')  ||
  ', COALESCE(MAX(' ||quote_ident(column_name)|| '), 0) + 1 ) FROM ' || quote_ident(table_name)
  AS sequence_fix,
  table_name
FROM information_schema.columns
WHERE column_default LIKE 'nextval%';
EOT
            );

        $fixes = [];
        $tableFilter = array_map(
            function ($name) {
                return trim($name, '"');
            },
            array_filter($tableFilter)
        );
        foreach ($queries as $query) {
            if (in_array($query['table_name'], $tableFilter)) {
                $fixes[] = $query['sequence_fix'];
            }
        }

        return $fixes;
    }

    private function getMigrationTemplate(): string
    {
        return <<<'EOT'
<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version<version> extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('SET session_replication_role = replica');
<up>

        $this->addSql('SET session_replication_role = DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('SET session_replication_role = replica');

        $this->addSql(
            [
<down>
            ]
        );

        $this->addSql('SET session_replication_role = DEFAULT');
    }
}

EOT;
    }
}
