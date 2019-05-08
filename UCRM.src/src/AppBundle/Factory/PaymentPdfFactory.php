<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Factory;

use AppBundle\Component\Generate\Pdf;
use AppBundle\Entity\Option;
use AppBundle\Entity\Payment;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityManager;

class PaymentPdfFactory
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
        $payments = $this->em->getRepository(Payment::class)->getByIds($ids);

        $pageSize = $this->options->get(Option::PDF_PAGE_SIZE_EXPORT, Pdf::PAGE_SIZE_US_LETTER);

        $html = $this->twig->render(
            'client/payment/overview.html.twig',
            [
                'payments' => $payments,
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
