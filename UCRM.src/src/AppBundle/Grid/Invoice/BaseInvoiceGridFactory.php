<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\Invoice;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\InvoiceController;
use AppBundle\DataProvider\InvoiceDataProvider;
use AppBundle\DataProvider\PaymentTokenDataProvider;
use AppBundle\Entity\User;
use AppBundle\Facade\Exception\CannotDeleteProcessedProformaException;
use AppBundle\Facade\InvoiceExportFacade;
use AppBundle\Facade\InvoiceFacade;
use AppBundle\Facade\OrganizationFacade;
use AppBundle\Security\Permission;
use AppBundle\Service\BadgeFactory;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Service\Invoice\InvoiceDueDateRenderer;
use AppBundle\Util\Formatter;
use RabbitMqBundle\RabbitMqEnqueuer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class BaseInvoiceGridFactory
{
    /**
     * @var GridFactory
     */
    protected $gridFactory;

    /**
     * @var GridHelper
     */
    protected $gridHelper;

    /**
     * @var Formatter
     */
    protected $formatter;

    /**
     * @var OrganizationFacade
     */
    protected $organizationFacade;

    /**
     * @var BadgeFactory
     */
    protected $badgeFactory;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var InvoiceDataProvider
     */
    protected $invoiceDataProvider;

    /**
     * @var InvoiceFacade
     */
    private $invoiceFacade;

    /**
     * @var DownloadResponseFactory
     */
    private $downloadResponseFactory;

    /**
     * @var InvoiceDueDateRenderer
     */
    private $invoiceDueDateRenderer;

    /**
     * @var PaymentTokenDataProvider
     */
    protected $paymentTokenDataProvider;

    /**
     * @var InvoiceExportFacade
     */
    private $invoiceExportFacade;

    /**
     * @var RabbitMqEnqueuer
     */
    private $rabbitMqEnqueuer;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        Formatter $formatter,
        OrganizationFacade $organizationFacade,
        BadgeFactory $badgeFactory,
        TokenStorageInterface $tokenStorage,
        InvoiceDataProvider $invoiceDataProvider,
        InvoiceFacade $invoiceFacade,
        DownloadResponseFactory $downloadResponseFactory,
        InvoiceDueDateRenderer $invoiceDueDateRenderer,
        InvoiceExportFacade $invoiceExportFacade,
        PaymentTokenDataProvider $paymentTokenDataProvider,
        RabbitMqEnqueuer $rabbitMqEnqueuer
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->formatter = $formatter;
        $this->organizationFacade = $organizationFacade;
        $this->badgeFactory = $badgeFactory;
        $this->tokenStorage = $tokenStorage;
        $this->invoiceDataProvider = $invoiceDataProvider;
        $this->invoiceFacade = $invoiceFacade;
        $this->downloadResponseFactory = $downloadResponseFactory;
        $this->invoiceDueDateRenderer = $invoiceDueDateRenderer;
        $this->invoiceExportFacade = $invoiceExportFacade;
        $this->paymentTokenDataProvider = $paymentTokenDataProvider;
        $this->rabbitMqEnqueuer = $rabbitMqEnqueuer;
    }

    public function renderDueDate(array $row): string
    {
        return $this->invoiceDueDateRenderer->renderDueDate($row[0]);
    }

    protected function exportPdfAction(Grid $grid): Response
    {
        $ids = $grid->getDoMultiActionIds() ?: $grid->getExportIds();
        $count = count($ids);

        if (1 === $count) {
            $pdf = $this->invoiceExportFacade->getMergedPdf($ids);
            if (null === $pdf) {
                $this->gridHelper->addTranslatedFlash('warning', 'There are no invoices to export.');

                return new RedirectResponse($grid->generateMultiActionReturnUrl());
            }

            return $this->downloadResponseFactory->createFromContent($pdf, 'invoices', 'pdf', 'application/pdf');
        }

        if (! $ids) {
            $this->gridHelper->addTranslatedFlash('warning', 'There are no invoices to export.');

            return new RedirectResponse($grid->generateMultiActionReturnUrl());
        }

        $this->invoiceExportFacade->preparePdfDownload(
            $this->gridHelper->transChoice(
                '%count% exported invoices',
                $count,
                [
                    '%count%' => $count,
                ]
            ),
            $ids,
            $this->getUser()
        );

        $this->gridHelper->addTranslatedFlash(
            'success',
            'Export was added to queue. You can download it in System > Tools > Downloads.',
            null,
            [
                '%link%' => $this->gridHelper->generateUrl('download_index'),
            ]
        );

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    protected function exportPdfOverviewAction(Grid $grid): Response
    {
        $ids = $grid->getDoMultiActionIds() ?: $grid->getExportIds();
        if (! $ids) {
            $this->gridHelper->addTranslatedFlash('warning', 'There are no invoices to export.');

            return new RedirectResponse($grid->generateMultiActionReturnUrl());
        }

        $count = count($ids);
        $this->invoiceExportFacade->preparePdfOverviewDownload(
            $this->gridHelper->transChoice(
                '%filetype% overview of %count% invoices',
                $count,
                [
                    '%count%' => $count,
                    '%filetype%' => 'PDF',
                ]
            ),
            $ids,
            $this->getUser()
        );

        $this->gridHelper->addTranslatedFlash(
            'success',
            'Export was added to queue. You can download it in System > Tools > Downloads.',
            null,
            [
                '%link%' => $this->gridHelper->generateUrl('download_index'),
            ]
        );

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    protected function exportCsvOverviewAction(Grid $grid): Response
    {
        $ids = $grid->getDoMultiActionIds() ?: $grid->getExportIds();
        if (! $ids) {
            $this->gridHelper->addTranslatedFlash('warning', 'There are no invoices to export.');

            return new RedirectResponse($grid->generateMultiActionReturnUrl());
        }

        $count = count($ids);
        $this->invoiceExportFacade->prepareCsvOverviewDownload(
            $this->gridHelper->transChoice(
                '%filetype% overview of %count% invoices',
                $count,
                [
                    '%count%' => $count,
                    '%filetype%' => 'CSV',
                ]
            ),
            $ids,
            $this->getUser()
        );

        $this->gridHelper->addTranslatedFlash(
            'success',
            'Export was added to queue. You can download it in System > Tools > Downloads.',
            null,
            [
                '%link%' => $this->gridHelper->generateUrl('download_index'),
            ]
        );

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    protected function multiDeleteAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, InvoiceController::class);

        $ids = $grid->getDoMultiActionIds();
        try {
            $this->invoiceFacade->handleDeleteMultipleIds($ids);

            $count = count($ids);
            $this->gridHelper->addTranslatedFlash(
                'success',
                '%count% items will be deleted in the background within a few minutes.',
                $count,
                [
                    '%count%' => $count,
                ]
            );
        } catch (CannotDeleteProcessedProformaException $exception) {
            $this->gridHelper->addTranslatedFlash(
                'error',
                'Processed proforma invoice cannot be deleted.'
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    protected function multiVoidAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, InvoiceController::class);

        list($voided, $failed) = $this->invoiceFacade->handleVoidMultiple($grid->getDoMultiActionIds());

        if ($voided > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                '%count% invoices will be voided in the background in a moment.',
                $voided,
                [
                    '%count%' => $voided,
                ]
            );
        }

        if ($failed > 0) {
            $this->gridHelper->addTranslatedFlash(
                'warning',
                '%count% invoices were already void.',
                $failed,
                [
                    '%count%' => $failed,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    protected function multiApproveAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, InvoiceController::class);

        $ids = $grid->getDoMultiActionIds();
        list($approved, $failed) = $this->invoiceFacade->handleAddToApproveQueueMultiple($ids);

        if ($approved > 0) {
            $msg = '%count% drafts will be approved in the background within a few minutes.';

            $this->gridHelper->addTranslatedFlash(
                'success',
                $msg,
                $approved,
                [
                    '%count%' => $approved,
                ]
            );
        }

        if ($failed > 0) {
            $msg = '%count% invoices were already approved.';

            $this->gridHelper->addTranslatedFlash(
                'warning',
                $msg,
                $failed,
                [
                    '%count%' => $failed,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    private function getUser(): User
    {
        $user = $this->tokenStorage->getToken()->getUser();
        assert($user instanceof User);

        return $user;
    }
}
