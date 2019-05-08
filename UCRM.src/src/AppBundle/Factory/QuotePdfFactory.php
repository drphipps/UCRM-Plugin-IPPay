<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory;

use AppBundle\Component\Generate\Pdf;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use AppBundle\Service\Quote\QuoteTotalsReportProvider;
use Doctrine\ORM\EntityManagerInterface;

class QuotePdfFactory
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var QuoteTotalsReportProvider
     */
    private $quoteTotalsReportProvider;

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
        QuoteTotalsReportProvider $quoteTotalsReportProvider,
        Options $options,
        Pdf $pdf,
        \Twig_Environment $twig
    ) {
        $this->entityManager = $entityManager;
        $this->quoteTotalsReportProvider = $quoteTotalsReportProvider;
        $this->options = $options;
        $this->pdf = $pdf;
        $this->twig = $twig;
    }

    public function createOverview(array $ids): string
    {
        $quotes = $this->entityManager->getRepository(Quote::class)->getExportableByIds($ids);

        list($totals, $organizations) = $this->quoteTotalsReportProvider->getTotalsReport($quotes);

        $pageSize = $this->options->get(Option::PDF_PAGE_SIZE_EXPORT, Pdf::PAGE_SIZE_US_LETTER);

        $html = $this->twig->render(
            'client/quote/overview.html.twig',
            [
                'quotes' => $quotes,
                'totals' => $totals,
                'organizations' => $organizations,
                'pageSize' => $pageSize,
            ]
        );

        $pdf = $this->pdf->generateFromHtml(
            $html,
            $pageSize,
            Pdf::PAGE_ORIENTATION_LANDSCAPE
        );

        return $pdf;
    }
}
