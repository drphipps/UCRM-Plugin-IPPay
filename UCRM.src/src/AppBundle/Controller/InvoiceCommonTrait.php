<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\Financial\Invoice;
use AppBundle\Handler\Invoice\PdfHandler;
use AppBundle\Service\DownloadResponseFactory;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait for methods needed in both Client Zone and Admin Zone.
 *
 * @property Container $container
 */
trait InvoiceCommonTrait
{
    private function handleDownloadPdf(Invoice $invoice): Response
    {
        $path = $this->container->get(PdfHandler::class)->getFullInvoicePdfPath($invoice);
        if (! $path) {
            throw $this->createNotFoundException();
        }

        return $this->container->get(DownloadResponseFactory::class)->createFromFile($path);
    }
}
