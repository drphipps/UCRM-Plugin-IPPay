<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory;

use AppBundle\Component\Generate\Pdf;
use AppBundle\Entity\ClientLogsView;
use AppBundle\Entity\Option;
use AppBundle\Service\ClientLogsView\ClientLogsViewConverter;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;

class ClientLogsViewPdfFactory
{
    /**
     * @var ClientLogsViewConverter
     */
    private $clientLogsConverter;

    /**
     * @var EntityManager
     */
    private $em;

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
        ClientLogsViewConverter $clientLogsConverter,
        EntityManager $em,
        Options $options,
        Pdf $pdf,
        \Twig_Environment $twig
    ) {
        $this->clientLogsConverter = $clientLogsConverter;
        $this->em = $em;
        $this->options = $options;
        $this->pdf = $pdf;
        $this->twig = $twig;
    }

    public function create(array $ids): string
    {
        $clientLogs = $this->em->getRepository(ClientLogsView::class)->getByIds($ids);

        $clientLogsConverted = [];
        foreach ($clientLogs as $clientLog) {
            $clientLogsConverted[] = $this->clientLogsConverter->convertToRowDataForView($clientLog);
        }

        $pageSize = $this->options->get(Option::PDF_PAGE_SIZE_EXPORT, Pdf::PAGE_SIZE_US_LETTER);

        $html = $this->twig->render(
            'client_logs_view/export_pdf.html.twig',
            [
                'clientLogs' => $clientLogsConverted,
                'pageSize' => $pageSize,
            ]
        );

        return $this->pdf->generateFromHtml(
            $html,
            $pageSize,
            Pdf::PAGE_ORIENTATION_LANDSCAPE
        );
    }
}
