<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Financial\FinancialEmailSender;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Facade\QuoteFacade;
use AppBundle\Form\QuoteCommentType;
use AppBundle\Handler\Quote\PdfHandler;
use AppBundle\RoutesMap\QuoteRoutesMap;
use AppBundle\Security\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property Container              $container
 * @property EntityManagerInterface $em
 */
trait QuoteActionsTrait
{
    /**
     * @var QuoteRoutesMap|null
     */
    private $quoteRoutesMap;

    private function handleDelete(Quote $quote, ?array $parameters = []): RedirectResponse
    {
        $this->container->get(QuoteFacade::class)->handleDelete($quote);
        $this->addTranslatedFlash('success', 'Quote has been deleted.');

        return $this->redirectToRoute($this->getQuoteRoutesMap()->quoteGrid, $parameters);
    }

    private function handleNoteAdd(Request $request, Quote $quote, FormInterface $noteForm): ?Response
    {
        $noteForm->handleRequest($request);

        if ($noteForm->isSubmitted() && $noteForm->isValid()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);

            $this->em->flush();
            $this->addTranslatedFlash('success', 'Note has been saved.');

            if ($request->isXmlHttpRequest()) {
                $noteForm = $this->createForm(QuoteCommentType::class, $quote);
                assert($noteForm instanceof FormInterface);

                $this->invalidateTemplate(
                    'quote__note',
                    'client/quote/components/show/notes.html.twig',
                    [
                        'quote' => $quote,
                        'client' => $quote->getClient(),
                        'noteForm' => $noteForm->createView(),
                    ]
                );

                return $this->createAjaxResponse();
            }

            return $this->redirectToRoute($this->getQuoteRoutesMap()->show, ['id' => $quote->getId()]);
        }

        return null;
    }

    private function handleSendQuoteEmail(Quote $quote): RedirectResponse
    {
        $this->container->get(FinancialEmailSender::class)->send($quote, NotificationTemplate::CLIENT_NEW_QUOTE);
        $this->addTranslatedFlash('info', 'Quote has been queued for sending.');

        return $this->redirectToRoute($this->getQuoteRoutesMap()->show, ['id' => $quote->getId()]);
    }

    private function handleAcceptQuote(Quote $quote): RedirectResponse
    {
        $this->container->get(QuoteFacade::class)->handleAccept($quote);
        $this->addTranslatedFlash('success', 'Quote has been accepted.');

        return $this->redirectToRoute($this->getQuoteRoutesMap()->show, ['id' => $quote->getId()]);
    }

    private function handleRejectQuote(Quote $quote): RedirectResponse
    {
        $this->container->get(QuoteFacade::class)->handleReject($quote);
        $this->addTranslatedFlash('success', 'Quote has been rejected.');

        return $this->redirectToRoute($this->getQuoteRoutesMap()->show, ['id' => $quote->getId()]);
    }

    private function handleReopenQuote(Quote $quote): RedirectResponse
    {
        $this->container->get(QuoteFacade::class)->handleReopen($quote);
        $this->addTranslatedFlash('success', 'Quote has been reopened.');

        return $this->redirectToRoute($this->getQuoteRoutesMap()->show, ['id' => $quote->getId()]);
    }

    private function handleRegeneratePdf(Quote $quote): RedirectResponse
    {
        try {
            $this->container->get(PdfHandler::class)->getFullQuotePdfPath($quote, true);
            $this->addTranslatedFlash('success', 'Quote PDF has been regenerated.');
        } catch (IOException | \InvalidArgumentException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute($this->getQuoteRoutesMap()->show, ['id' => $quote->getId()]);
    }
}
