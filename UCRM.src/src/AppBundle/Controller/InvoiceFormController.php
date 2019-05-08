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
use AppBundle\Entity\Fee;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Financial\InvoiceItem;
use AppBundle\Entity\Financial\InvoiceItemFee;
use AppBundle\Entity\Financial\Quote;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Facade\FinancialFormFacade;
use AppBundle\Factory\Financial\FinancialFactory;
use AppBundle\Factory\Financial\FinancialItemFeeFactory;
use AppBundle\Factory\Financial\ItemFormIteratorFactory;
use AppBundle\Form\InvoiceType;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\Financial\FinancialTemplateFileManager;
use AppBundle\Service\Financial\NextFinancialNumberFactory;
use AppBundle\Service\InvoiceCalculations;
use AppBundle\Service\Options;
use AppBundle\Transformer\Financial\FinancialToInvoiceTransformer;
use AppBundle\Transformer\Financial\FormCollectionsTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Exception\DeadlockException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client/invoice")
 * @PermissionControllerName(InvoiceController::class)
 */
class InvoiceFormController extends BaseController
{
    private const SIGNAL_RECALCULATE = 'recalculate';

    /**
     * @Route("/new/{id}", name="client_invoice_new", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request, Client $client): Response
    {
        if ($client->getIsLead()) {
            $this->addTranslatedFlash('error', 'This action is not possible, while the client is lead.');

            return $this->redirectToRoute(
                'client_show',
                [
                    'id' => $client->getId(),
                ]
            );
        }

        $createProformaInvoice = $client->getGenerateProformaInvoices()
            ?? $this->get(Options::class)->get(Option::GENERATE_PROFORMA_INVOICES);

        return $this->handleNewEditAction(
            $request,
            $createProformaInvoice
                ? $this->get(FinancialFactory::class)->createProformaInvoice($client, new \DateTimeImmutable())
                : $this->get(FinancialFactory::class)->createInvoice($client, new \DateTimeImmutable())
        );
    }

    /**
     * @Route("/{id}/edit", name="client_invoice_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, Invoice $invoice): Response
    {
        if (! $invoice->isEditable()) {
            $this->addTranslatedFlash('error', 'Only unpaid invoice can be edited.');

            return $this->redirectToRoute('client_invoice_show', ['id' => $invoice->getId()]);
        }

        if ($invoice->getInvoiceStatus() === Invoice::PAID) {
            $invoice->setInvoiceStatus(Invoice::UNPAID);
        }

        return $this->handleNewEditAction($request, $invoice);
    }

    private function handleNewEditAction(Request $request, Invoice $invoice): Response
    {
        $client = $invoice->getClient();
        $isEdit = $invoice->getId() !== null;

        if (! $isEdit) {
            $this->addRequestedItems($invoice, $request);
        }

        // Generate invoice number when editing draft.
        if ($invoice->getInvoiceStatus() === Invoice::DRAFT) {
            $invoice->setInvoiceNumber(
                $this->get(NextFinancialNumberFactory::class)->createInvoiceNumber($invoice->getOrganization())
            );
        }

        $form = $this->createForm(
            InvoiceType::class,
            $invoice,
            [
                'display_apply_credit_toggle' => (! $isEdit || $invoice->getInvoiceStatus() === Invoice::DRAFT)
                    && $client
                    && $client->getAccountStandingsCredit() > 0,
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ]
        );
        $this->get(FormCollectionsTransformer::class)->fillFormDataWithSavedItems($form, $invoice);

        // When creating new invoice, set "apply credit" toggle to true as default.
        if (! $isEdit && $form->has(InvoiceType::APPLY_CREDIT)) {
            $form->get(InvoiceType::APPLY_CREDIT)->setData(true);
        }

        // We need to handle collection updates manually as they do not correspond with entity.
        $oldInvoiceItems = $invoice->getItemsSorted();

        // Old fee IDs are used to handle display of the "+ Add fee" button items.
        $oldFeeIds = [];
        $oldInvoiceItems->map(
            function (InvoiceItem $invoiceItem) use (&$oldFeeIds) {
                if ($invoiceItem instanceof InvoiceItemFee && $fee = $invoiceItem->getFee()) {
                    $oldFeeIds[] = $fee->getId();
                }
            }
        );

        if ($isEdit) {
            $invoiceBeforeUpdate = clone $invoice;
        }
        $form->handleRequest($request);

        // Contains all invoice items present after submitting the form. Used together with $oldInvoiceItems to handle collection updates.
        $formInvoiceItems = new ArrayCollection();
        if ($form->isSubmitted()) {
            $formInvoiceItems = $this->get(FormCollectionsTransformer::class)->getItemsFromFormData(
                $form,
                $invoice
            );
            if ($formInvoiceItems->isEmpty()) {
                $form->addError(new FormError('Invoice must have at least one item.'));
            }

            if ($invoice->isProforma()) {
                if (
                    ! $this->get(FinancialTemplateFileManager::class)
                        ->existsTwig($invoice->getProformaInvoiceTemplate())
                ) {
                    $form->get('proformaInvoiceTemplate')->addError(
                        new FormError('Template files not found. This template cannot be used.')
                    );
                }
            } else {
                if (! $this->get(FinancialTemplateFileManager::class)->existsTwig($invoice->getInvoiceTemplate())) {
                    $form->get('invoiceTemplate')->addError(
                        new FormError('Template files not found. This template cannot be used.')
                    );
                }
            }

            // Recalculation is done server side by submitting the form with signal parameter.
            if ($response = $this->processRecalculateFormSignal($invoice, $form, $request, $oldInvoiceItems)) {
                return $response;
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->get(FinancialFormFacade::class)->handleSubmitInvoice(
                    $form,
                    $invoice,
                    $oldInvoiceItems,
                    $form->has(InvoiceType::APPLY_CREDIT) && $form->get(InvoiceType::APPLY_CREDIT)->getData(),
                    $invoiceBeforeUpdate ?? null
                );

                if ($isEdit) {
                    $this->addTranslatedFlash('success', 'Invoice has been saved.');
                } else {
                    $this->addTranslatedFlash('success', 'Invoice has been created.');
                }

                /** @var SubmitButton $sendAndSaveButton */
                $sendAndSaveButton = $form->get('sendAndSave');
                if ($sendAndSaveButton->isClicked()) {
                    $this->get(FinancialEmailSender::class)->send(
                        $invoice,
                        $invoice->isProforma()
                            ? NotificationTemplate::CLIENT_NEW_PROFORMA_INVOICE
                            : NotificationTemplate::CLIENT_NEW_INVOICE
                    );
                    $this->addTranslatedFlash('info', 'Invoice has been queued for sending.');
                }

                return $this->redirect(
                    $this->generateUrl(
                        $invoice->isProforma() ? 'client_show_proformas' : 'client_show_invoices',
                        [
                            'id' => $client->getId(),
                        ]
                    )
                );
            } catch (TemplateRenderException | \Dompdf\Exception $exception) {
                $this->addTranslatedFlash('error', 'Invoice template contains errors and can\'t be safely used.');
            } catch (DeadlockException $exception) {
                $this->addTranslatedFlash(
                    'error',
                    'The form submission failed because of concurrent update in database. Please submit the form again.'
                );
            }
        }

        $formView = $form->createView();

        return $this->render(
            $isEdit ? 'client/invoice/edit.html.twig' : 'client/invoice/new.html.twig',
            array_merge(
                $this->get(FinancialFormDataProvider::class)->getViewData(
                    $formInvoiceItems,
                    $oldInvoiceItems,
                    $client,
                    $invoice
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
        Invoice $invoice,
        FormInterface $form,
        Request $request,
        Collection $oldInvoiceItems
    ): ?JsonResponse {
        if (! $form->isSubmitted() || $request->request->get('signal') !== self::SIGNAL_RECALCULATE) {
            return null;
        }

        $this->get(FinancialFormFacade::class)->processForm($form, $invoice, $oldInvoiceItems);

        if ($form->has(InvoiceType::APPLY_CREDIT) && $form->get(InvoiceType::APPLY_CREDIT)->getData()) {
            $credit = $this->get(InvoiceCalculations::class)->calculatePotentialCredit($invoice);
            $amountToPay = $credit->amountToPay;
            $amountPotentiallyPaidFromCredit = $credit->amountFromCredit;
        } else {
            $amountToPay = $invoice->getTotal();
            $amountPotentiallyPaidFromCredit = 0.0;
        }

        return new JsonResponse(
            [
                'total' => $amountToPay,
                'totalRoundingDifference' => $invoice->getTotalRoundingDifference(),
                'subtotal' => $invoice->getSubtotal(),
                'discount' => $invoice->getTotalDiscount(),
                'taxes' => $invoice->getTotalTaxes(),
                'totalBeforeCredit' => $invoice->getTotal(),
                'credit' => $amountPotentiallyPaidFromCredit,
            ]
        );
    }

    private function addRequestedItems(Invoice $invoice, Request $request): void
    {
        // Fees
        $fee = $this->em->find(Fee::class, $request->query->getInt('fee_id'));
        if ($fee && (! $fee->getService() || $fee->getService()->getStatus() !== Service::STATUS_QUOTED)) {
            $item = $this->get(FinancialItemFeeFactory::class)->createInvoiceItem($fee);
            $item->setInvoice($invoice);
            $invoice->getInvoiceItems()->add($item);
            $fee->setInvoiced(true);
        }

        // Quote
        if ($quote = $this->em->find(Quote::class, $request->query->getInt('quote_id'))) {
            $invoice->setDiscountInvoiceLabel($quote->getDiscountInvoiceLabel());
            $invoice->setDiscountType($quote->getDiscountType());
            $invoice->setDiscountValue($quote->getDiscountValue());
            $items = $this->get(FinancialToInvoiceTransformer::class)->getInvoiceItemsFromFinancial($quote);
            foreach ($items as $item) {
                $item->setInvoice($invoice);
                $invoice->getInvoiceItems()->add($item);
            }
        }
    }
}
