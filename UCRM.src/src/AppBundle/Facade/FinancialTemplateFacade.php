<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\DataProvider\AccountStatementTemplateDataProvider;
use AppBundle\DataProvider\InvoiceTemplateDataProvider;
use AppBundle\DataProvider\ProformaInvoiceTemplateDataProvider;
use AppBundle\DataProvider\QuoteTemplateDataProvider;
use AppBundle\Entity\Financial\AccountStatementTemplate;
use AppBundle\Entity\Financial\FinancialTemplateInterface;
use AppBundle\Entity\Financial\InvoiceTemplate;
use AppBundle\Entity\Financial\ProformaInvoiceTemplate;
use AppBundle\Entity\Financial\QuoteTemplate;
use AppBundle\Exception\TemplateImportExportException;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Form\Data\TemplateImportData;
use AppBundle\Service\Financial\FinancialTemplateFileManager;
use AppBundle\Service\Financial\FinancialTemplateRenderer;
use AppBundle\Util\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FinancialTemplateFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var FinancialTemplateRenderer
     */
    private $financialTemplateRenderer;

    /**
     * @var FinancialTemplateFileManager
     */
    private $financialTemplateFileManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var InvoiceTemplateDataProvider
     */
    private $invoiceTemplateDataProvider;

    /**
     * @var QuoteTemplateDataProvider
     */
    private $quoteTemplateDataProvider;

    /**
     * @var AccountStatementTemplateDataProvider
     */
    private $accountStatementTemplateDataProvider;

    /**
     * @var ProformaInvoiceTemplateDataProvider
     */
    private $proformaInvoiceTemplateDataProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        FinancialTemplateRenderer $financialTemplateRenderer,
        FinancialTemplateFileManager $financialTemplateFileManager,
        TranslatorInterface $translator,
        InvoiceTemplateDataProvider $invoiceTemplateDataProvider,
        QuoteTemplateDataProvider $quoteTemplateDataProvider,
        AccountStatementTemplateDataProvider $accountStatementTemplateDataProvider,
        ProformaInvoiceTemplateDataProvider $proformaInvoiceTemplateDataProvider
    ) {
        $this->entityManager = $entityManager;
        $this->financialTemplateRenderer = $financialTemplateRenderer;
        $this->financialTemplateFileManager = $financialTemplateFileManager;
        $this->translator = $translator;
        $this->invoiceTemplateDataProvider = $invoiceTemplateDataProvider;
        $this->quoteTemplateDataProvider = $quoteTemplateDataProvider;
        $this->accountStatementTemplateDataProvider = $accountStatementTemplateDataProvider;
        $this->proformaInvoiceTemplateDataProvider = $proformaInvoiceTemplateDataProvider;
    }

    public function handleCreate(FinancialTemplateInterface $template, string $twig, string $css): void
    {
        $this->entityManager->transactional(
            function () use ($template, $twig, $css) {
                $this->entityManager->persist($template);
                $this->entityManager->flush();
                $this->saveTemplateFiles($template, $twig, $css);

                try {
                    $this->financialTemplateRenderer->testAgainstDummyData($template);
                } catch (TemplateRenderException | \Dompdf\Exception $exception) {
                    $template->setIsValid(false);
                }
            }
        );
    }

    public function handleUpdate(FinancialTemplateInterface $template, string $twig, string $css): void
    {
        $this->entityManager->transactional(
            function () use ($template, $twig, $css) {
                $this->saveTemplateFiles($template, $twig, $css);
                $template->setErrorNotificationSent(false);
                $this->entityManager->flush();

                try {
                    $this->financialTemplateRenderer->testAgainstDummyData($template);
                    $template->setIsValid(true);
                } catch (TemplateRenderException | \Dompdf\Exception $exception) {
                    $template->setIsValid(false);
                }
            }
        );
    }

    public function handleClone(FinancialTemplateInterface $template): FinancialTemplateInterface
    {
        $cloned = clone $template;

        $this->entityManager->transactional(
            function () use ($cloned, $template) {
                $cloned->setName(
                    sprintf(
                        '%s (%s)',
                        $template->getName(),
                        $this->translator->trans('clone')
                    )
                );
                $cloned->setOfficialName(null);
                $cloned->setCreatedDate(new \DateTime());
                $cloned->setErrorNotificationSent(false);
                $this->entityManager->persist($cloned);
                $this->entityManager->flush();
                $this->financialTemplateFileManager->clone($template, $cloned);

                try {
                    $this->financialTemplateRenderer->testAgainstDummyData($cloned);
                    $cloned->setIsValid(true);
                } catch (TemplateRenderException | \Dompdf\Exception $exception) {
                    $cloned->setIsValid(false);
                }
            }
        );

        return $cloned;
    }

    public function handleDelete(FinancialTemplateInterface $template): bool
    {
        if (
            $template->getOfficialName()
            || (
                $template instanceof InvoiceTemplate
                && $this->invoiceTemplateDataProvider->isUsedOnOrganization($template)
            )
            || (
                $template instanceof ProformaInvoiceTemplate
                && $this->proformaInvoiceTemplateDataProvider->isUsedOnOrganization($template)
            )
            || (
                $template instanceof QuoteTemplate
                && $this->quoteTemplateDataProvider->isUsedOnOrganization($template)
            )
            || (
                $template instanceof AccountStatementTemplate
                && $this->accountStatementTemplateDataProvider->isUsedOnOrganization($template)
            )
        ) {
            return false;
        }

        $this->entityManager->transactional(
            function () use ($template) {
                if (
                    (
                        $template instanceof InvoiceTemplate
                        && $this->invoiceTemplateDataProvider->isUsedOnInvoice($template)
                    )
                    ||
                    (
                        $template instanceof ProformaInvoiceTemplate
                        && $this->proformaInvoiceTemplateDataProvider->isUsedOnInvoice($template)
                    )
                    || (
                        $template instanceof QuoteTemplate
                        && $this->quoteTemplateDataProvider->isUsedOnQuote($template)
                    )
                ) {
                    $template->setDeletedAt(new \DateTime());
                } else {
                    $invoiceTemplateBeforeDelete = clone $template;
                    $this->entityManager->remove($template);
                    $this->entityManager->flush();
                    $this->financialTemplateFileManager->delete($invoiceTemplateBeforeDelete);
                }
            }
        );

        return true;
    }

    public function handleExport(FinancialTemplateInterface $template): string
    {
        $twig = $this->financialTemplateFileManager->getSource(
            $template,
            FinancialTemplateFileManager::TWIG_FILENAME
        );
        $css = $this->financialTemplateFileManager->getSource(
            $template,
            FinancialTemplateFileManager::CSS_FILENAME
        );

        $zip = new \ZipArchive();
        $tmpFile = Helpers::getTemporaryFile();
        if (true !== $zip->open($tmpFile, \ZipArchive::CREATE)) {
            throw new TemplateImportExportException('ZIP archive could not be created.');
        }

        $zip->addFromString(FinancialTemplateFileManager::TWIG_FILENAME, $twig);
        $zip->addFromString(FinancialTemplateFileManager::CSS_FILENAME, $css);
        $zip->close();

        return $tmpFile;
    }

    public function handleImportInvoiceTemplate(TemplateImportData $import): InvoiceTemplate
    {
        $invoiceTemplate = new InvoiceTemplate();
        $this->handleImport($invoiceTemplate, $import);

        return $invoiceTemplate;
    }

    public function handleImportProformaInvoiceTemplate(TemplateImportData $import): ProformaInvoiceTemplate
    {
        $proformaInvoiceTemplate = new ProformaInvoiceTemplate();
        $this->handleImport($proformaInvoiceTemplate, $import);

        return $proformaInvoiceTemplate;
    }

    public function handleImportAccountStatementTemplate(TemplateImportData $import): AccountStatementTemplate
    {
        $accountStatementTemplate = new AccountStatementTemplate();
        $this->handleImport($accountStatementTemplate, $import);

        return $accountStatementTemplate;
    }

    public function handleImportQuoteTemplate(TemplateImportData $import): QuoteTemplate
    {
        $quoteTemplate = new QuoteTemplate();
        $this->handleImport($quoteTemplate, $import);

        return $quoteTemplate;
    }

    private function handleImport(FinancialTemplateInterface $template, TemplateImportData $import): void
    {
        $template->setName($import->name);

        $file = $import->file;
        $zip = new \ZipArchive();
        if (true !== $zip->open($file->getRealPath())) {
            throw new TemplateImportExportException('ZIP archive could not be opened.');
        }

        $twig = $zip->getFromName(FinancialTemplateFileManager::TWIG_FILENAME);
        if (false === $twig) {
            throw new TemplateImportExportException('Twig template could not be found in the ZIP archive.');
        }

        $css = $zip->getFromName(FinancialTemplateFileManager::CSS_FILENAME);
        if (false === $css) {
            throw new TemplateImportExportException('CSS template could not be found in the ZIP archive.');
        }

        $this->handleCreate($template, $twig, $css);
    }

    private function saveTemplateFiles(FinancialTemplateInterface $template, string $twig, string $css): void
    {
        $this->financialTemplateFileManager->saveTwig($template, $twig);
        $this->financialTemplateFileManager->saveCss($template, $css);
    }
}
