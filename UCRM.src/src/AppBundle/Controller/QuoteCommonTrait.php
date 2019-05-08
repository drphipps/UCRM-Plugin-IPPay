<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\Financial\Quote;
use AppBundle\Handler\Quote\PdfHandler;
use AppBundle\Service\DownloadResponseFactory;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait for methods needed in both Client Zone and Admin Zone.
 *
 * @property Container $container
 */
trait QuoteCommonTrait
{
    private function handleDownloadPdf(Quote $quote): Response
    {
        $path = $this->container->get(PdfHandler::class)->getFullQuotePdfPath($quote);
        if (! $path) {
            throw $this->createNotFoundException();
        }

        return $this->container->get(DownloadResponseFactory::class)->createFromFile($path);
    }
}
