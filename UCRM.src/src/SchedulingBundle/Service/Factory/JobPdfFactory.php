<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Service\Factory;

use AppBundle\Component\Generate\Pdf;
use AppBundle\Entity\Option;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;
use SchedulingBundle\Entity\Job;

class JobPdfFactory
{
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

    public function __construct(EntityManager $em, Options $options, Pdf $pdf, \Twig_Environment $twig)
    {
        $this->em = $em;
        $this->options = $options;
        $this->pdf = $pdf;
        $this->twig = $twig;
    }

    public function create(array $ids): string
    {
        $jobs = $this->em->getRepository(Job::class)->getByIds($ids);

        $pageSize = $this->options->get(Option::PDF_PAGE_SIZE_EXPORT, Pdf::PAGE_SIZE_US_LETTER);

        $html = $this->twig->render(
            '@scheduling/agenda/export_pdf.html.twig',
            [
                'jobs' => $jobs,
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
