<?php

declare(strict_types=1);

namespace AppBundle\Migrations;

use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class Version20190306093018 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Version $version)
    {
        parent::__construct($version);

        $this->filesystem = new Filesystem();
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $proformaInvoiceTemplates = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('proforma_invoice_template')
            ->andWhere('official_name IS NULL')
            ->execute()
            ->fetchAll();

        $quoteTemplates = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('quote_template')
            ->andWhere('official_name IS NULL')
            ->execute()
            ->fetchAll();

        $rootDir = rtrim($this->container->getParameter('kernel.root_dir'), '/');
        $proformaInvoiceTemplatesDir = $rootDir . '/data/proforma_invoice_templates';

        $finder = new Finder();
        try {
            $finder
                ->directories()
                ->in($rootDir . '/data/quote_templates');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            // dir doesn't exist, do not use it
            return;
        }

        $quoteTemplatesIds = array_column($quoteTemplates, 'id');
        $proformaInvoiceTemplatesIds = array_column($proformaInvoiceTemplates, 'id');

        $directories = [];
        // Prepare directory names for next foreach(). Rename directory inside iterated Finder fails.
        foreach ($finder as $dir) {
            $directories[] = $dir;
        }

        foreach ($directories as $dir) {
            if ($this->filesystem->exists(sprintf('%s/%s', $proformaInvoiceTemplatesDir, $dir->getFilename()))) {
                continue;
            }

            if (! in_array((int) $dir->getFilename(), $quoteTemplatesIds, true)) {
                $this->filesystem->rename(
                    $dir->getRealPath(),
                    sprintf('%s/%s', $proformaInvoiceTemplatesDir, $dir->getFilename())
                );
            } elseif (
                in_array((int) $dir->getFilename(), $proformaInvoiceTemplatesIds, true)
                && ! $this->filesystem->exists(sprintf('%s/%s', $proformaInvoiceTemplatesDir, $dir->getFilename()))
            ) {
                $this->filesystem->mkdir(
                    sprintf('%s/%s', $proformaInvoiceTemplatesDir, $dir->getFilename())
                );
                $this->filesystem->touch(
                    sprintf('%s/%s/%s', $proformaInvoiceTemplatesDir, $dir->getFilename(), 'template.css')
                );
                $this->filesystem->touch(
                    sprintf('%s/%s/%s', $proformaInvoiceTemplatesDir, $dir->getFilename(), 'template.html.twig')
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
