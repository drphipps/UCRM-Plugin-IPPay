<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Payment\ReceiptSender;
use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Payment;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Form\Data\PaymentBatchData;
use AppBundle\Form\Data\PaymentBatchItemData;
use AppBundle\Form\PaymentBatchType;
use AppBundle\Form\PaymentNoteType;
use AppBundle\Form\PaymentType;
use AppBundle\Grid\Payment\PaymentGridFactory;
use AppBundle\Handler\Payment\PdfHandler;
use AppBundle\Security\Permission;
use AppBundle\Security\SpecialPermission;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Service\NotificationFactory;
use AppBundle\Service\Payment\PaymentReceiptTemplateRenderer;
use AppBundle\Util\Formatter;
use AppBundle\Util\Helpers;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/billing/payments")
 */
class PaymentController extends BaseController
{
    public const SIGNAL_CLIENT_CHANGED = 'client-changed';

    /**
     * @Route("", name="payment_index")
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function indexAction(Request $request, bool $onlyUnmatched = false): Response
    {
        $grid = $this->get(PaymentGridFactory::class)->create($onlyUnmatched);
        if ($response = $grid->processMultiAction()) {
            return $response;
        }
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'payments/index.html.twig',
            [
                'paymentsGrid' => $grid,
                'onlyUnmatched' => $onlyUnmatched,
            ]
        );
    }

    /**
     * @Route("/only-unmatched", name="payment_index_only_unmatched")
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function indexOnlyUnmatchedAction(Request $request): Response
    {
        return $this->indexAction($request, true);
    }

    /**
     * @Route("/new", name="payment_new")
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function newAction(Request $request): Response
    {
        if (! $this->isPermissionGranted(Permission::EDIT, self::class)) {
            $this->denyAccessUnlessPermissionGranted(SpecialPermission::ALLOWED, SpecialPermission::PAYMENT_CREATE);
        }

        $payment = new Payment();

        $paymentFacade = $this->get(PaymentFacade::class);
        $paymentFacade->setDefaults($payment, null, $this->getUser());

        $options = [
            'action' => $this->generateUrl('payment_new'),
            'organization' => null,
        ];
        $form = $this->createForm(PaymentType::class, $payment, $options);
        $form->get('sendReceipt')->setData($this->getOption(Option::SEND_PAYMENT_RECEIPTS));
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($response = $this->processInvoiceFormSignal($payment, $form, $request)) {
                return $response;
            }

            if ($form->get('sendReceipt')->getData()) {
                if ($form->get('client')->isEmpty()) {
                    $form->get('client')->addError(new FormError('Client is not selected.'));
                } elseif (($client = $form->get('client')->getData()) && ! $client->hasBillingEmail()) {
                    $this->addTranslatedFlash('warning', 'Client does not have Billing email.');
                }
            }

            if ($form->isValid()) {
                $paymentFacade->handleCreate(
                    $payment,
                    $form->get('invoices')->getData()->toArray(),
                    null,
                    (bool) $form->get('sendReceipt')->getData()
                );
                $this->addTranslatedFlash('success', 'Payment has been created.');

                if ($payment->getCredit()) {
                    $this->addTranslatedFlash(
                        'info',
                        '%amount% has been added to credit.',
                        null,
                        [
                            '%amount%' => $this->container->get(Formatter::class)->formatCurrency(
                                $payment->getCredit()->getAmount(),
                                $payment->getCurrency()->getCode()
                            ),
                        ]
                    );
                }

                return $this->createAjaxRedirectResponse(
                    'payment_show',
                    [
                        'id' => $payment->getId(),
                    ]
                );
            }
        }

        if ($payment->getClient()) {
            $payment->setCurrency($payment->getClient()->getOrganization()->getCurrency());
        }

        return $this->render(
            'payments/components/add_form.html.twig',
            [
                'form' => $form->createView(),
                'payment' => $payment,
            ]
        );
    }

    /**
     * @Route("/new-batch", name="payment_new_batch")
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function newBatchAction(Request $request): Response
    {
        if (! $this->isPermissionGranted(Permission::EDIT, self::class)) {
            $this->denyAccessUnlessPermissionGranted(SpecialPermission::ALLOWED, SpecialPermission::PAYMENT_CREATE);
        }

        $payment = new Payment();
        $paymentFacade = $this->get(PaymentFacade::class);
        $paymentFacade->setDefaults($payment, null, $this->getUser());

        $paymentBatchData = new PaymentBatchData();
        $paymentBatchData->method = $payment->getMethod();
        $paymentBatchData->createdDate = $payment->getCreatedDate();
        $paymentBatchData->sendReceipt = $this->getOption(Option::SEND_PAYMENT_RECEIPTS);
        for ($i = 0; $i < 4; ++$i) {
            $newItem = new PaymentBatchItemData();
            $newItem->sendReceipt = $paymentBatchData->sendReceipt;
            $paymentBatchData->payments->add($newItem);
        }

        $form = $this->createForm(
            PaymentBatchType::class,
            $paymentBatchData,
            [
                'action' => $this->generateUrl('payment_new_batch'),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $paymentsCreated = 0;
            $paymentsFailed = 0;

            foreach ($paymentBatchData->payments as $key => $paymentBatchItemData) {
                $paymentItem = new Payment();
                $paymentItem->setClient($paymentBatchItemData->client);
                $paymentItem->setCurrency($paymentBatchItemData->client->getOrganization()->getCurrency());
                $paymentItem->setAmount($paymentBatchItemData->amount);
                if ($paymentBatchData->method === Payment::METHOD_CHECK) {
                    $paymentItem->setCheckNumber($paymentBatchItemData->checkNumber);
                }
                $paymentItem->setNote($paymentBatchItemData->note);
                $paymentItem->setMethod($paymentBatchData->method);
                $paymentItem->setCreatedDate($paymentBatchData->createdDate);
                $paymentItem->setUser($payment->getUser());

                $chosenInvoices = $form->get('payments')->get($key)->get('invoices')->getData();
                $invoices = [];
                if (! $chosenInvoices->isEmpty()) {
                    $invoices = $this->em->getRepository(Invoice::class)->findBy(
                        [
                            'id' => $chosenInvoices->toArray(),
                            'currency' => $paymentItem->getCurrency(),
                            'client' => $paymentItem->getClient(),
                        ]
                    );
                }

                try {
                    $paymentFacade->handleCreate(
                        $paymentItem,
                        $invoices,
                        null,
                        $paymentBatchItemData->sendReceipt
                    );
                    ++$paymentsCreated;
                } catch (\Exception $ex) {
                    ++$paymentsFailed;
                }
            }

            if ($paymentsFailed > 0) {
                $this->addTranslatedFlash(
                    'error',
                    '%count% payments have failed.',
                    $paymentsFailed,
                    ['%count%' => $paymentsFailed]
                );
            }
            if ($paymentsCreated > 0) {
                $this->addTranslatedFlash(
                    'success',
                    '%count% payments have been created.',
                    $paymentsCreated,
                    ['%count%' => $paymentsCreated]
                );
            }

            return $this->createAjaxRedirectResponse(
                'payment_index'
            );
        }

        $organization = $this->em->getRepository(Organization::class)->getFirstSelected();

        return $this->render(
            'payments/components/add_batch_modal.html.twig',
            [
                'form' => $form->createView(),
                'collectionNonce' => Helpers::generateNonce(),
                'defaultCurrency' => $organization ? $organization->getCurrency() : null,
            ]
        );
    }

    /**
     * @Route("/{id}/payable-invoices", name="payment_invoice_list", options={"expose": true}, requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function payableInvoicesAction(Client $client, Request $request): Response
    {
        $payment = new Payment();
        $paymentFacade = $this->get(PaymentFacade::class);
        $paymentFacade->setDefaults($payment, null, $this->getUser());
        $payment->setClient($client);
        $form = $this->createForm(PaymentType::class, $payment, ['organization' => $client->getOrganization()]);
        $form->setData($payment);

        return $this->processInvoiceFormSignal($payment, $form, $request, true)
            ?? $this->createAjaxResponse([], false);
    }

    /**
     * @Route("/{id}", name="payment_show", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("view")
     */
    public function showAction(Payment $payment, Request $request): Response
    {
        $notification = $this->get(NotificationFactory::class)->create();
        $template = $this->em->getRepository(NotificationTemplate::class)->find(
            NotificationTemplate::CLIENT_PAYMENT_RECEIPT
        );
        $notification->setBodyTemplate($template->getBody());

        $showMatch = $showUnmatch = false;
        if ($payment->isMatched()) {
            $showUnmatch = $this->em->getRepository(Payment::class)->isRemovalPossible($payment);
        } else {
            $showMatch = true;
        }

        $details = $payment->getProvider() && $payment->getPaymentDetailsId()
            ? $this->em->find(
                $payment->getProvider()->getPaymentDetailsClass(),
                $payment->getPaymentDetailsId()
            )
            : null;

        $noteForm = $this->createForm(PaymentNoteType::class, $payment);
        $noteForm->handleRequest($request);

        if ($noteForm->isSubmitted() && $noteForm->isValid()) {
            $this->denyAccessUnlessPermissionGranted(Permission::EDIT, self::class);

            $this->get(PaymentFacade::class)->handleUpdateWithoutProcess($payment);

            $this->addTranslatedFlash('success', 'Note has been saved.');

            if ($request->isXmlHttpRequest()) {
                $this->invalidateTemplate(
                    'payment__note',
                    'payments/components/edit/notes.html.twig',
                    [
                        'payment' => $payment,
                        'noteForm' => $this->createForm(PaymentNoteType::class, $payment)->createView(),
                    ]
                );

                return $this->createAjaxResponse();
            }

            return $this->redirectToRoute('payment_show', ['id' => $payment->getId()]);
        }

        return $this->render(
            'payments/show.html.twig',
            [
                'noteForm' => $noteForm->createView(),
                'payment' => $payment,
                'details' => $details,
                'receipt' => $notification->getBodyTemplate(),
                'showMatch' => $showMatch,
                'showUnmatch' => $showUnmatch,
                'hasBillingEmail' => $payment->getClient() ? (bool) $payment->getClient()->getBillingEmails() : false,
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="payment_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(Payment $payment): Response
    {
        if ($this->get(PaymentFacade::class)->handleDelete($payment)) {
            $this->addTranslatedFlash('success', 'Payment has been deleted.');
        } else {
            $this->addTranslatedFlash('error', 'Payment with a refund cannot be deleted.');
        }

        return $this->redirectToRoute('payment_index');
    }

    /**
     * @Route("/{id}/match", name="payment_match", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function matchAction(Request $request, Payment $payment): Response
    {
        if ($payment->isMatched()) {
            $this->addTranslatedFlash('warning', 'Payment is already matched.');

            return $this->createAjaxRedirectResponse(
                'payment_show',
                [
                    'id' => $payment->getId(),
                ]
            );
        }

        $paymentBeforeUpdate = clone $payment;

        $options = [
            'action' => $this->generateUrl(
                'payment_match',
                [
                    'id' => $payment->getId(),
                ]
            ),
            'set_client_required' => true,
            'organization' => null,
        ];
        $form = $this->createForm(PaymentType::class, $payment, $options);
        $form->handleRequest($request);

        if ($form->isSubmitted() && ($response = $this->processInvoiceFormSignal($payment, $form, $request))) {
            return $response;
        }

        if ($form->isSubmitted() && $form->get('client')->isEmpty()) {
            $form->get('client')->addError(new FormError('This field is required'));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if (! $payment->getCurrency() && $payment->getClient()) {
                $payment->setCurrency($payment->getClient()->getOrganization()->getCurrency());
            }

            $this->get(PaymentFacade::class)->handleUpdate(
                $payment,
                $paymentBeforeUpdate,
                $form->get('invoices')->getData()->toArray(),
                (bool) $form->get('sendReceipt')->getData()
            );
            $this->addTranslatedFlash('success', 'Payment has been matched.');

            return $this->createAjaxRedirectResponse(
                'payment_show',
                [
                    'id' => $payment->getId(),
                ]
            );
        }

        return $this->render(
            'payments/components/add_form.html.twig',
            [
                'form' => $form->createView(),
                'payment' => $payment,
            ]
        );
    }

    /**
     * @Route("/{id}/unmatch", name="payment_unmatch", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function unmatchAction(Payment $payment): Response
    {
        if (! $this->em->getRepository(Payment::class)->isRemovalPossible($payment)) {
            $this->addTranslatedFlash('error', 'Payment with a refund cannot be unmatched.');

            return $this->redirectToRoute('payment_index');
        }

        $this->get(PaymentFacade::class)->handleUnmatch($payment);

        $this->addTranslatedFlash('success', 'Payment has been unmatched.');

        return $this->redirectToRoute('payment_index');
    }

    /**
     * @Route("/{id}/send-receipt", name="payment_send_receipt", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function sendReceiptAction(Payment $payment): Response
    {
        if (! $payment->getClient()) {
            $this->addTranslatedFlash('error', 'Receipts can be sent for matched payments only.');
        } else {
            try {
                $this->get(ReceiptSender::class)->send($payment);
                $this->addTranslatedFlash('success', 'Receipt has been sent to client.');
            } catch (TemplateRenderException $exception) {
                $this->addTranslatedFlash('error', 'Receipt has not been sent. Receipt template is invalid.');
            }
        }

        return $this->redirectToRoute(
            'payment_show',
            [
                'id' => $payment->getId(),
            ]
        );
    }

    /**
     * @Route("/{id}/pdf-receipt", name="payment_get_pdf_receipt", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function getPdfReceiptAction(Payment $payment): Response
    {
        if (! $payment->getClient()) {
            $this->addTranslatedFlash('error', 'Receipts can be downloaded for matched payments only.');

            return $this->redirectToRoute(
                'payment_show',
                [
                    'id' => $payment->getId(),
                ]
            );
        }

        try {
            $path = $this->get(PdfHandler::class)->getFullPaymentReceiptPdfPath($payment);
        } catch (TemplateRenderException $exception) {
            $this->addTranslatedFlash(
                'error',
                // rel="noopener noreferrer" added in EN translation, kept original here for other translations to work
                // the link is internal, so there is no security concern
                'Receipt template contains errors. You can fix it in <a href="%link%" target="_blank">System &rightarrow; Customization &rightarrow; Receipt templates</a>.',
                null,
                [
                    '%link%' => $this->generateUrl(
                        'payment_receipt_template_show',
                        [
                            'id' => $payment->getClient()->getOrganization()->getPaymentReceiptTemplate()->getId(),
                        ]
                    ),
                ]
            );

            return $this->redirectToRoute(
                'payment_show',
                [
                    'id' => $payment->getId(),
                ]
            );
        }

        if (! $path) {
            throw $this->createNotFoundException();
        }

        return $this->get(DownloadResponseFactory::class)->createFromFile($path);
    }

    /**
     * @Route("/{id}/html-receipt", name="payment_get_html_receipt", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function getHtmlReceiptAction(Payment $payment): Response
    {
        if (! $payment->getClient()) {
            return $this->render(
                'payments/components/view/receipt_error.html.twig',
                [
                    'errorMessage' => $this->trans('Receipts can be downloaded for matched payments only.'),
                ]
            );
        }

        $nonce = Helpers::generateNonce();
        try {
            $html = $this->get(PaymentReceiptTemplateRenderer::class)->renderPaymentReceipt(
                $payment,
                $payment->getClient()->getOrganization()->getPaymentReceiptTemplate(),
                true,
                $nonce
            );
        } catch (TemplateRenderException $exception) {
            return $this->render(
                'payments/components/view/receipt_error.html.twig',
                [
                    'errorMessage' => $this->trans(
                        // rel="noopener noreferrer" added in EN translation, kept original here for other translations to work
                        // the link is internal, so there is no security concern
                        'Receipt template contains errors. You can fix it in <a href="%link%" target="_blank">System &rightarrow; Customization &rightarrow; Receipt templates</a>.',
                        [
                            '%link%' => $this->generateUrl(
                                'payment_receipt_template_show',
                                [
                                    'id' => $payment->getClient()->getOrganization()
                                        ->getPaymentReceiptTemplate()->getId(),
                                ]
                            ),
                        ]
                    ),
                ]
            );
        }

        $response = new Response($html);
        $response->headers->set('Content-Security-Policy', sprintf('script-src \'nonce-%s\'', $nonce));

        return $response;
    }

    private function processInvoiceFormSignal(
        Payment $payment,
        FormInterface $form,
        Request $request,
        bool $isBatch = false
    ): ?Response {
        if ($request->get('signal') !== self::SIGNAL_CLIENT_CHANGED) {
            return null;
        }

        $this->invalidateTemplate(
            'client-invoices-select',
            $isBatch
                ? 'payments/components/edit/invoices_batch.html.twig'
                : 'payments/components/edit/invoices.html.twig',
            [
                'payment' => $payment,
                'form' => $form->createView(),
            ]
        );

        return $this->createAjaxResponse([], false);
    }
}
