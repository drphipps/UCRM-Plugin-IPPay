<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory;

use AppBundle\Component\Generate\Pdf;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Option;
use AppBundle\Service\Invoice\InvoiceTotalsReportProvider;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManagerInterface;

class InvoicePdfFactory
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var InvoiceTotalsReportProvider
     */
    private $invoiceTotalsReportProvider;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var Pdf
     */
    private $pdf;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(
        EntityManagerInterface $entityManager,
        InvoiceTotalsReportProvider $invoiceTotalsReportProvider,
        Options $options,
        Pdf $pdf,
        \Twig_Environment $twig
    ) {
        $this->entityManager = $entityManager;
        $this->invoiceTotalsReportProvider = $invoiceTotalsReportProvider;
        $this->options = $options;
        $this->pdf = $pdf;
        $this->twig = $twig;
    }

    /**
     * @param int[] $ids
     */
    public function createOverviewHtml(array $ids, string $pageSize): string
    {
        $invoices = $this->entityManager->getRepository(Invoice::class)->getExportableByIds($ids);

        list($totals, $organizations) = $this->invoiceTotalsReportProvider->getTotalsReport($invoices);

        $html = $this->twig->render(
            'client/invoice/overview.html.twig',
            [
                'invoices' => $invoices,
                'totals' => $totals,
                'organizations' => $organizations,
                'pageSize' => $pageSize,
            ]
        );

        return $html;
    }

    /**
     * @param int[] $ids
     */
    public function createOverview(array $ids): string
    {
        $pageSize = $this->options->get(Option::PDF_PAGE_SIZE_EXPORT, Pdf::PAGE_SIZE_US_LETTER);

        $html = $this->createOverviewHtml($ids, $pageSize);

        $pdf = $this->pdf->generateFromHtml(
            $html,
            $pageSize,
            Pdf::PAGE_ORIENTATION_LANDSCAPE
        );

        return $pdf;
    }
}
