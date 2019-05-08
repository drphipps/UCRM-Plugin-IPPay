<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Service\Financial;

use AppBundle\Entity\Financial\AccountStatementTemplate;
use AppBundle\Entity\Financial\FinancialTemplateInterface;
use AppBundle\Entity\Financial\InvoiceTemplate;
use AppBundle\Entity\Financial\ProformaInvoiceTemplate;
use AppBundle\Entity\Financial\QuoteTemplate;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

class FinancialTemplateFileManager
{
    public const TWIG_FILENAME = 'template.html.twig';
    public const CSS_FILENAME = 'template.css';

    /**
     * @var string
     */
    private $customInvoiceTemplatePath;

    /**
     * @var string
     */
    private $officialInvoiceTemplatePath;

    /**
     * @var string
     */
    private $customAccountStatementTemplatePath;

    /**
     * @var string
     */
    private $officialAccountStatementTemplatePath;

    /**
     * @var string
     */
    private $customQuoteTemplatePath;

    /**
     * @var string
     */
    private $officialQuoteTemplatePath;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $officialProformaInvoiceTemplatePath;

    /**
     * @var string
     */
    private $customProformaInvoiceTemplatePath;

    public function __construct(
        string $customInvoiceTemplatePath,
        string $officialInvoiceTemplatePath,
        string $customAccountStatementTemplatePath,
        string $officialAccountStatementTemplatePath,
        string $customQuoteTemplatePath,
        string $officialQuoteTemplatePath,
        string $officialProformaInvoiceTemplatePath,
        string $customProformaInvoiceTemplatePath
    ) {
        $this->customInvoiceTemplatePath = $customInvoiceTemplatePath;
        $this->officialInvoiceTemplatePath = $officialInvoiceTemplatePath;
        $this->customAccountStatementTemplatePath = $customAccountStatementTemplatePath;
        $this->officialAccountStatementTemplatePath = $officialAccountStatementTemplatePath;
        $this->customQuoteTemplatePath = $customQuoteTemplatePath;
        $this->officialQuoteTemplatePath = $officialQuoteTemplatePath;
        $this->filesystem = new Filesystem();
        $this->officialProformaInvoiceTemplatePath = $officialProformaInvoiceTemplatePath;
        $this->customProformaInvoiceTemplatePath = $customProformaInvoiceTemplatePath;
    }

    public function getSource(FinancialTemplateInterface $template, string $file): string
    {
        $path = $this->getPath($template, $file);
        if (! $this->filesystem->exists($path)) {
            throw new FileNotFoundException(sprintf('Financial template file could not be found in "%s".', $path));
        }

        return file_get_contents($path);
    }

    public function existsTwig(FinancialTemplateInterface $template): bool
    {
        return $this->filesystem->exists($this->getPath($template, self::TWIG_FILENAME));
    }

    public function saveTwig(FinancialTemplateInterface $template, string $content): void
    {
        $this->checkOfficial($template);

        $this->filesystem->dumpFile(
            $this->getCustomPath($template, self::TWIG_FILENAME),
            $content
        );
    }

    public function saveCss(FinancialTemplateInterface $template, string $content): void
    {
        $this->checkOfficial($template);

        $this->filesystem->dumpFile(
            $this->getCustomPath($template, self::CSS_FILENAME),
            $content
        );
    }

    public function delete(FinancialTemplateInterface $template): void
    {
        $this->filesystem->remove($this->getCustomPath($template, null));
    }

    public function clone(FinancialTemplateInterface $origin, FinancialTemplateInterface $target): void
    {
        $this->filesystem->mirror(
            $this->getPath($origin, null),
            $this->getCustomPath($target, null),
            null,
            [
                'override' => true,
                'delete' => true,
            ]
        );
    }

    private function checkOfficial(FinancialTemplateInterface $template): void
    {
        if ($template->getOfficialName()) {
            throw new \InvalidArgumentException('Not supported for official templates.');
        }
    }

    private function getPath(FinancialTemplateInterface $template, ?string $file): string
    {
        if ($template->getOfficialName()) {
            return $this->getOfficialPath($template, $file);
        }

        return $this->getCustomPath($template, $file);
    }

    private function getCustomPath(FinancialTemplateInterface $template, ?string $file): string
    {
        switch (true) {
            case $template instanceof InvoiceTemplate:
                $templatePath = $this->customInvoiceTemplatePath;
                break;
            case $template instanceof ProformaInvoiceTemplate:
                $templatePath = $this->customProformaInvoiceTemplatePath;
                break;
            case $template instanceof AccountStatementTemplate:
                $templatePath = $this->customAccountStatementTemplatePath;
                break;
            case $template instanceof QuoteTemplate:
            default:
                $templatePath = $this->customQuoteTemplatePath;
        }

        return implode(
            '/',
            array_filter(
                [
                    $templatePath,
                    $template->getId(),
                    $file,
                ]
            )
        );
    }

    private function getOfficialPath(FinancialTemplateInterface $template, ?string $file): string
    {
        switch (true) {
            case $template instanceof InvoiceTemplate:
                $templatePath = $this->officialInvoiceTemplatePath;
                break;
            case $template instanceof ProformaInvoiceTemplate:
                $templatePath = $this->officialProformaInvoiceTemplatePath;
                break;
            case $template instanceof AccountStatementTemplate:
                $templatePath = $this->officialAccountStatementTemplatePath;
                break;
            case $template instanceof QuoteTemplate:
            default:
                $templatePath = $this->officialQuoteTemplatePath;
        }

        return implode(
            '/',
            array_filter(
                [
                    $templatePath,
                    $template->getOfficialName(),
                    $file,
                ]
            )
        );
    }
}
