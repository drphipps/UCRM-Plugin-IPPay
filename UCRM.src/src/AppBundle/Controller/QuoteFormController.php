<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Financial\FinancialEmailSender;
use AppBundle\DataProvider\FinancialFormDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\Financial\QuoteItem;
use AppBundle\Entity\Financial\QuoteItemFee;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Service;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Facade\FinancialFormFacade;
use AppBundle\Factory\Financial\FinancialFactory;
use AppBundle\Factory\Financial\FinancialItemServiceFactory;
use AppBundle\Factory\Financial\FinancialItemSurchargeFactory;
use AppBundle\Factory\Financial\ItemFormIteratorFactory;
use AppBundle\Form\QuoteType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\Financial\FinancialTemplateFileManager;
use AppBundle\Service\Financial\NextFinancialNumberFactory;
use AppBundle\Transformer\Financial\FormCollectionsTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client/quote")
 * @PermissionControllerName(QuoteController::class)
 */
class QuoteFormController extends BaseController
{
    private const SIGNAL_RECALCULATE = 'recalculate';

    /**
     * @Route("/new/{id}", name="client_quote_new", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request, Client $client): Response
    {
        return $this->handleNewEditAction(
            $request,
            $this->get(FinancialFactory::class)->createQuote($client, new \DateTimeImmutable())
        );
    }

    /**
     * @Route("/{id}/edit", name="client_quote_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, Quote $quote): Response
    {
        return $this->handleNewEditAction($request, $quote);
    }

    private function handleNewEditAction(Request $request, Quote $quote): Response
    {
        $client = $quote->getClient();
        $isEdit = null !== $quote->getId();

        if (! $isEdit) {
            $this->addRequestedItems($quote, $request);
        }

        if ($quote->getQuoteNumber() === null) {
            $quote->setQuoteNumber(
                $this->get(NextFinancialNumberFactory::class)->createQuoteNumber($quote->getOrganization())
            );
        }

        $form = $this->createForm(
            QuoteType::class,
            $quote,
            [
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ]
        );
        $this->get(FormCollectionsTransformer::class)->fillFormDataWithSavedItems($form, $quote);

        // We need to handle collection updates manually as they do not correspond with entity.
        $oldQuoteItems = $quote->getItemsSorted();

        // Old fee IDs are used to handle display of the "+ Add fee" button items.
        $oldFeeIds = [];
        $oldQuoteItems->map(
            function (QuoteItem $quoteItem) use (&$oldFeeIds) {
                if ($quoteItem instanceof QuoteItemFee && $quoteItem->getFee()) {
                    $oldFeeIds[] = $quoteItem->getFee()->getId();
                }
            }
        );

        if ($isEdit) {
            $quoteBeforeUpdate = clone $quote;
        }
        $form->handleRequest($request);

        // Contains all quote items present after submitting the form. Used together with $oldQuoteItems to handle collection updates.
        $formQuoteItems = new ArrayCollection();
        if ($form->isSubmitted()) {
            $formQuoteItems = $this->get(FormCollectionsTransformer::class)->getItemsFromFormData(
                $form,
                $quote
            );
            if ($formQuoteItems->isEmpty()) {
                $form->addError(new FormError('Quote must have at least one item.'));
            }

            if (! $this->get(FinancialTemplateFileManager::class)->existsTwig($quote->getQuoteTemplate())) {
                $form->get('invoiceTemplate')->addError(
                    new FormError('Template files not found. This template cannot be used.')
                );
            }

            // Recalculation is done server side by submitting the form with signal parameter.
            if ($response = $this->processRecalculateFormSignal($quote, $form, $request, $oldQuoteItems)) {
                return $response;
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->get(FinancialFormFacade::class)->handleSubmitQuote(
                    $form,
                    $quote,
                    $oldQuoteItems,
                    $quoteBeforeUpdate ?? null
                );

                if ($isEdit) {
                    $this->addTranslatedFlash('success', 'Quote has been saved.');
                } else {
                    $this->addTranslatedFlash('success', 'Quote has been created.');
                }

                /** @var SubmitButton $sendAndSaveButton */
                $sendAndSaveButton = $form->get('sendAndSave');
                if ($sendAndSaveButton->isClicked()) {
                    $this->get(FinancialEmailSender::class)->send($quote, NotificationTemplate::CLIENT_NEW_QUOTE);
                    $this->addTranslatedFlash('info', 'Quote has been queued for sending.');
                }

                return $this->redirect(
                    $this->generateUrl(
                        'client_quote_show',
                        [
                            'id' => $quote->getId(),
                        ]
                    )
                );
            } catch (TemplateRenderException | \Dompdf\Exception $exception) {
                $this->addTranslatedFlash('error', 'Quote template contains errors and can\'t be safely used.');
            }
        }

        $formView = $form->createView();

        return $this->render(
            $isEdit ? 'client/quote/edit.html.twig' : 'client/quote/new.html.twig',
            array_merge(
                $this->get(FinancialFormDataProvider::class)->getViewData(
                    $formQuoteItems,
                    $oldQuoteItems,
                    $client,
                    $quote
                ),
                [
                    'oldFeeIds' => $oldFeeIds,
                    'form' => $formView,
                    'invoiceItemFields' => $this->get(ItemFormIteratorFactory::class)->create($formView),
                ]
            )
        );
    }

    private function processRecalculateFormSignal(
        Quote $quote,
        FormInterface $form,
        Request $request,
        Collection $oldQuoteItems
    ): ?JsonResponse {
        if (! $form->isSubmitted() || $request->request->get('signal') !== self::SIGNAL_RECALCULATE) {
            return null;
        }

        $this->get(FinancialFormFacade::class)->processForm($form, $quote, $oldQuoteItems);

        return new JsonResponse(
            [
                'total' => $quote->getTotal(),
                'subtotal' => $quote->getSubtotal(),
                'totalRoundingDifference' => $quote->getTotalRoundingDifference(),
                'discount' => $quote->getTotalDiscount(),
                'taxes' => $quote->getTotalTaxes(),
            ]
        );
    }

    private function addRequestedItems(Quote $quote, Request $request): void
    {
        // Service and Surcharges
        if ($service = $this->em->find(Service::class, $request->query->getInt('service_id'))) {
            $item = $this->get(FinancialItemServiceFactory::class)->createDefaultQuoteItem($service);
            $item->setQuote($quote);
            $quote->getQuoteItems()->add($item);

            $financialItemSurchargeFactory = $this->get(FinancialItemSurchargeFactory::class);
            foreach ($service->getServiceSurcharges() as $surcharge) {
                $item = $financialItemSurchargeFactory->createQuoteItem($surcharge);
                $item->setQuote($quote);
                $quote->getQuoteItems()->add($item);
            }
        }
    }
}
