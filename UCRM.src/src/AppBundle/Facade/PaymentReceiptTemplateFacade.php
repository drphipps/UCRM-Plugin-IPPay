<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Facade;

use AppBundle\Component\Generate\Pdf;
use AppBundle\DataProvider\PaymentReceiptTemplateDataProvider;
use AppBundle\Entity\PaymentReceiptTemplate;
use AppBundle\Exception\TemplateImportExportException;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\FileManager\PaymentReceiptTemplateFileManager;
use AppBundle\Form\Data\TemplateImportData;
use AppBundle\Service\Options;
use AppBundle\Service\Payment\PaymentReceiptTemplateRenderer;
use AppBundle\Util\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PaymentReceiptTemplateFacade
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PaymentReceiptTemplateFileManager
     */
    private $paymentReceiptTemplateFileManager;

    /**
     * @var Pdf
     */
    private $pdf;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var PaymentReceiptTemplateDataProvider
     */
    private $paymentReceiptTemplateDataProvider;

    /**
     * @var PaymentReceiptTemplateRenderer
     */
    private $paymentReceiptTemplateRenderer;

    public function __construct(
        EntityManagerInterface $entityManager,
        PaymentReceiptTemplateFileManager $paymentReceiptTemplateFileManager,
        Pdf $pdf,
        TranslatorInterface $translator,
        Options $options,
        PaymentReceiptTemplateDataProvider $paymentReceiptTemplateDataProvider,
        PaymentReceiptTemplateRenderer $paymentReceiptTemplateRenderer
    ) {
        $this->entityManager = $entityManager;
        $this->paymentReceiptTemplateFileManager = $paymentReceiptTemplateFileManager;
        $this->pdf = $pdf;
        $this->translator = $translator;
        $this->options = $options;
        $this->paymentReceiptTemplateDataProvider = $paymentReceiptTemplateDataProvider;
        $this->paymentReceiptTemplateRenderer = $paymentReceiptTemplateRenderer;
    }

    public function handleCreate(PaymentReceiptTemplate $template, string $twig, string $css): void
    {
        $this->entityManager->transactional(
            function () use ($template, $twig, $css) {
                $this->entityManager->persist($template);
                $this->entityManager->flush();
                $this->saveTemplateFiles($template, $twig, $css);

                try {
                    $this->paymentReceiptTemplateRenderer->testAgainstDummyData($template);
                } catch (TemplateRenderException | \Dompdf\Exception $exception) {
                    $template->setIsValid(false);
                }
            }
        );
    }

    public function handleUpdate(PaymentReceiptTemplate $template, string $twig, string $css): void
    {
        $this->entityManager->transactional(
            function () use ($template, $twig, $css) {
                $this->saveTemplateFiles($template, $twig, $css);
                $template->setErrorNotificationSent(false);
                $this->entityManager->flush();

                try {
                    $this->paymentReceiptTemplateRenderer->testAgainstDummyData($template);
                    $template->setIsValid(true);
                } catch (TemplateRenderException | \Dompdf\Exception $exception) {
                    $template->setIsValid(false);
                }
            }
        );
    }

    public function handleClone(PaymentReceiptTemplate $template): PaymentReceiptTemplate
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
                $this->paymentReceiptTemplateFileManager->clone($template, $cloned);

                try {
                    $this->paymentReceiptTemplateRenderer->testAgainstDummyData($cloned);
                    $cloned->setIsValid(true);
                } catch (TemplateRenderException | \Dompdf\Exception $exception) {
                    $cloned->setIsValid(false);
                }
            }
        );

        return $cloned;
    }

    public function handleDelete(PaymentReceiptTemplate $template): bool
    {
        if (
            $template->getOfficialName()
            || $this->paymentReceiptTemplateDataProvider->isUsedOnOrganization($template)
        ) {
            return false;
        }

        $this->entityManager->transactional(
            function () use ($template) {
                if ($this->paymentReceiptTemplateDataProvider->isUsedOnPayment($template)) {
                    $template->setDeletedAt(new \DateTime());
                } else {
                    $invoiceTemplateBeforeDelete = clone $template;
                    $this->entityManager->remove($template);
                    $this->entityManager->flush();
                    $this->paymentReceiptTemplateFileManager->delete($invoiceTemplateBeforeDelete);
                }
            }
        );

        return true;
    }

    public function handleExport(PaymentReceiptTemplate $template): string
    {
        $twig = $this->paymentReceiptTemplateFileManager->getSource(
            $template,
            PaymentReceiptTemplateFileManager::TWIG_FILENAME
        );
        $css = $this->paymentReceiptTemplateFileManager->getSource(
            $template,
            PaymentReceiptTemplateFileManager::CSS_FILENAME
        );

        $zip = new \ZipArchive();
        $tmpFile = Helpers::getTemporaryFile();
        if (true !== $zip->open($tmpFile, \ZipArchive::CREATE)) {
            throw new TemplateImportExportException('ZIP archive could not be created.');
        }

        $zip->addFromString(PaymentReceiptTemplateFileManager::TWIG_FILENAME, $twig);
        $zip->addFromString(PaymentReceiptTemplateFileManager::CSS_FILENAME, $css);
        $zip->close();

        return $tmpFile;
    }

    public function handleImport(TemplateImportData $import): PaymentReceiptTemplate
    {
        $template = new PaymentReceiptTemplate();
        $template->setName($import->name);

        $file = $import->file;
        $zip = new \ZipArchive();
        if (true !== $zip->open($file->getRealPath())) {
            throw new TemplateImportExportException('ZIP archive could not be opened.');
        }

        $twig = $zip->getFromName(PaymentReceiptTemplateFileManager::TWIG_FILENAME);
        if (false === $twig) {
            throw new TemplateImportExportException('Twig template could not be found in the ZIP archive.');
        }

        $css = $zip->getFromName(PaymentReceiptTemplateFileManager::CSS_FILENAME);
        if (false === $css) {
            throw new TemplateImportExportException('CSS template could not be found in the ZIP archive.');
        }

        $this->handleCreate($template, $twig, $css);

        return $template;
    }

    private function saveTemplateFiles(PaymentReceiptTemplate $template, string $twig, string $css): void
    {
        $this->paymentReceiptTemplateFileManager->saveTwig($template, $twig);
        $this->paymentReceiptTemplateFileManager->saveCss($template, $css);
    }
}
