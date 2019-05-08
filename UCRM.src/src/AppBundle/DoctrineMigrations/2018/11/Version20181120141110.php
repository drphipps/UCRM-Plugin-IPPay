<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class Version20181120141110 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $installedVersion = $this->connection->fetchColumn(
            'SELECT value FROM general WHERE code = ?',
            [
                'crm_installed_version',
            ]
        );

        if ($installedVersion === '2.0.3') {
            // clean install

            // An explanation story for @enumag:
            //
            // Once upon a time, new version numbers found home in the database migrations.
            // They were living happily there, but soon a big bad problem came.
            // And the problem said:
            //   "Hello version numbers, I would like to know who you are even before you are back home at the database."
            // But this could not be done and huge sorrow came upon the version numbers.
            // Then, a solution appeared. "Why don't we move to a parameter?", the version numbers said.
            // And so it happened.
            // But not everyone wanted to move there, there was a number called "2.0.3",
            // which said it does not want to move away from database migration.
            // And so from that time, the new version numbers are always welcomed by "2.0.3" when they move away
            // from the parameter file to the database.

            $channel = $this->container->getParameter('version_stability') === 'beta'
                ? 'beta'
                : 'stable';
        } elseif (stripos($installedVersion, 'beta') !== false) {
            // updating from beta

            $channel = 'beta';
        }

        $this->addSql(
            'INSERT INTO option (code, value) VALUES (?, ?)',
            [
                'UPDATE_CHANNEL',
                $channel ?? 'stable',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql(
            'DELETE FROM option WHERE code = ?',
            [
                'UPDATE_CHANNEL',
            ]
        );
    }
}
