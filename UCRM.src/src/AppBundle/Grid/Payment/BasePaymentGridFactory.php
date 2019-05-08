<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Grid\Payment;

use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\PaymentController;
use AppBundle\DataProvider\PaymentDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\Payment;
use AppBundle\Entity\User;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Facade\OrganizationFacade;
use AppBundle\Facade\PaymentFacade;
use AppBundle\Handler\Payment\PdfHandler;
use AppBundle\Security\Permission;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Util\Formatter;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class BasePaymentGridFactory
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
     * @var PaymentFacade
     */
    protected $paymentFacade;

    /**
     * @var OrganizationFacade
     */
    protected $organizationFacade;

    /**
     * @var DownloadResponseFactory
     */
    protected $downloadResponseFactory;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var PdfHandler
     */
    private $pdfHandler;

    /**
     * @var PaymentDataProvider
     */
    protected $paymentDataProvider;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        Formatter $formatter,
        PaymentFacade $paymentFacade,
        OrganizationFacade $organizationFacade,
        DownloadResponseFactory $downloadResponseFactory,
        TokenStorageInterface $tokenStorage,
        EntityManager $em,
        PdfHandler $pdfHandler,
        PaymentDataProvider $paymentDataProvider
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->formatter = $formatter;
        $this->paymentFacade = $paymentFacade;
        $this->organizationFacade = $organizationFacade;
        $this->downloadResponseFactory = $downloadResponseFactory;
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
        $this->pdfHandler = $pdfHandler;
        $this->paymentDataProvider = $paymentDataProvider;
    }

    protected function multiUnmatchAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, PaymentController::class);

        list($unmatched, $failed) = $this->paymentFacade->handleUnmatchMultiple($grid->getDoMultiActionIds());

        if ($unmatched > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                'Unmatched %count% payments.',
                $unmatched,
                [
                    '%count%' => $unmatched,
                ]
            );
        }

        if ($failed > 0) {
            $this->gridHelper->addTranslatedFlash(
                'warning',
                '%count% payments could not be unmatched.',
                $failed,
                [
                    '%count%' => $failed,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    protected function multiDeleteAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, PaymentController::class);

        list($deleted, $failed) = $this->paymentFacade->handleDeleteMultipleByIds($grid->getDoMultiActionIds());

        if ($deleted > 0) {
            $this->gridHelper->addTranslatedFlash(
                'success',
                'Deleted %count% payments.',
                $deleted,
                [
                    '%count%' => $deleted,
                ]
            );
        }

        if ($failed > 0) {
            $this->gridHelper->addTranslatedFlash(
                'warning',
                '%count% payments could not be deleted.',
                $failed,
                [
                    '%count%' => $failed,
                ]
            );
        }

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    protected function exportPdfOverviewAction(Grid $grid): Response
    {
        $ids = $grid->getDoMultiActionIds() ?: $grid->getExportIds();
        if (! $ids) {
            $this->gridHelper->addTranslatedFlash('warning', 'There are no payments to export.');

            return new RedirectResponse($grid->generateMultiActionReturnUrl());
        }

        $count = count($ids);
        $this->paymentFacade->preparePdfDownload(
            $this->gridHelper->transChoice(
                '%filetype% overview of %count% payments',
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
            $this->gridHelper->addTranslatedFlash('warning', 'There are no payments to export.');

            return new RedirectResponse($grid->generateMultiActionReturnUrl());
        }

        $count = count($ids);
        $this->paymentFacade->prepareCsvDownload(
            $this->gridHelper->transChoice(
                '%filetype% overview of %count% payments',
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

    protected function exportQuickBooksCsvAction(Grid $grid): Response
    {
        $ids = $grid->getDoMultiActionIds() ?: $grid->getExportIds();
        if (! $ids) {
            $this->gridHelper->addTranslatedFlash('warning', 'There are no payments to export.');

            return new RedirectResponse($grid->generateMultiActionReturnUrl());
        }

        $count = count($ids);
        $this->paymentFacade->prepareQuickBooksCsvDownload(
            $this->gridHelper->transChoice(
                '%filetype% overview of %count% payments',
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

    protected function exportPdfReceiptAction(Grid $grid): Response
    {
        $paymentRepository = $this->em->getRepository(Payment::class);
        $ids = $paymentRepository->filterMatchedIds($grid->getDoMultiActionIds() ?: $grid->getExportIds());
        if (! $ids) {
            $this->gridHelper->addTranslatedFlash('warning', 'There are no payments to export.');

            return new RedirectResponse($grid->generateMultiActionReturnUrl());
        }

        $count = count($ids);
        if ($count === 1) {
            $payment = $paymentRepository->find(reset($ids));
            if (! $payment || ! $payment->getClient()) {
                $this->gridHelper->addTranslatedFlash('warning', 'There are no payments to export.');

                return new RedirectResponse($grid->generateMultiActionReturnUrl());
            }

            try {
                $path = $this->pdfHandler->getFullPaymentReceiptPdfPath($payment);
            } catch (TemplateRenderException $exception) {
                $this->gridHelper->addTranslatedFlash(
                    'error',
                    // rel="noopener noreferrer" added in EN translation, kept original here for other translations to work
                    // the link is internal, so there is no security concern
                    'Receipt template contains errors. You can fix it in <a href="%link%" target="_blank">System &rightarrow; Customization &rightarrow; Receipt templates</a>.',
                    null,
                    [
                        '%link%' => $this->gridHelper->generateUrl(
                            'payment_receipt_template_show',
                            [
                                'id' => $payment->getClient()->getOrganization()->getPaymentReceiptTemplate()->getId(),
                            ]
                        ),
                    ]
                );

                return new RedirectResponse($grid->generateMultiActionReturnUrl());
            }

            if (! $path) {
                $this->gridHelper->addTranslatedFlash('warning', 'There are no payments to export.');

                return new RedirectResponse($grid->generateMultiActionReturnUrl());
            }

            return $this->downloadResponseFactory->createFromFile($path);
        }

        $this->paymentFacade->prepareReceiptPdfDownload(
            $this->gridHelper->transChoice(
                '%filetype% of %count% payment receipts',
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

    /**
     * @return string[]
     */
    protected function getPaymentMethodsForFilter(?Client $client = null): array
    {
        $usedMethods = $this->em->getRepository(Payment::class)
            ->getUsedPaymentMethods($client);

        return array_map(
            function ($method) {
                return $this->gridHelper->trans($method);
            },
            array_filter(
                Payment::METHOD_TYPE,
                function (int $key) use ($usedMethods) {
                    return in_array($key, $usedMethods, true);
                },
                ARRAY_FILTER_USE_KEY
            )
        );
    }

    private function getUser(): User
    {
        $user = $this->tokenStorage->getToken()->getUser();
        assert($user instanceof User);

        return $user;
    }
}
