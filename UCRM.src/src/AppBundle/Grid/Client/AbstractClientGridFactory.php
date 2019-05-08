<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Grid\Client;

use AppBundle\Component\Grid\Column\BaseColumn;
use AppBundle\Component\Grid\Grid;
use AppBundle\Component\Grid\GridFactory;
use AppBundle\Component\Grid\GridHelper;
use AppBundle\Controller\ClientController;
use AppBundle\DataProvider\ClientDataProvider;
use AppBundle\DataProvider\ClientTagDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Facade\ClientFacade;
use AppBundle\Facade\Exception\CannotCancelClientSubscriptionException;
use AppBundle\Facade\Exception\CannotDeleteDemoClientException;
use AppBundle\Facade\OrganizationFacade;
use AppBundle\Repository\ClientRepository;
use AppBundle\Security\Permission;
use AppBundle\Service\Client\ClientBalanceFormatter;
use AppBundle\Util\Formatter;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractClientGridFactory
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
     * @var ClientFacade
     */
    protected $clientFacade;

    /**
     * @var OrganizationFacade
     */
    protected $organizationFacade;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var ClientDataProvider
     */
    protected $clientDataProvider;

    /**
     * @var ClientTagDataProvider
     */
    protected $clientTagDataProvider;

    /**
     * @var ClientBalanceFormatter
     */
    private $clientBalanceFormatter;

    public function __construct(
        GridFactory $gridFactory,
        GridHelper $gridHelper,
        ClientFacade $clientFacade,
        OrganizationFacade $organizationFacade,
        \Twig_Environment $twig,
        ClientDataProvider $clientDataProvider,
        ClientTagDataProvider $clientTagDataProvider,
        ClientBalanceFormatter $clientBalanceFormatter
    ) {
        $this->gridFactory = $gridFactory;
        $this->gridHelper = $gridHelper;
        $this->clientFacade = $clientFacade;
        $this->organizationFacade = $organizationFacade;
        $this->twig = $twig;
        $this->clientDataProvider = $clientDataProvider;
        $this->clientTagDataProvider = $clientTagDataProvider;
        $this->clientBalanceFormatter = $clientBalanceFormatter;
    }

    protected function multiArchiveAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, ClientController::class);

        $ids = $grid->getDoMultiActionIds();
        $this->clientFacade->handleArchiveMultiple($ids);

        $count = count($ids);

        $this->gridHelper->addTranslatedFlash(
            'success',
            '%count% items will be archived in the background within a few minutes.',
            $count,
            [
                '%count%' => $count,
            ]
        );

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    protected function multiDeleteAction(Grid $grid): RedirectResponse
    {
        $this->gridHelper->denyAccessUnlessPermissionGranted(Permission::EDIT, ClientController::class);

        $ids = $grid->getDoMultiActionIds();
        try {
            $this->clientFacade->handleDeleteMultiple($ids);
        } catch (CannotDeleteDemoClientException $exception) {
            $this->gridHelper->addTranslatedFlash('error', 'This client cannot be deleted in demo.');

            return new RedirectResponse($grid->generateMultiActionReturnUrl());
        } catch (CannotCancelClientSubscriptionException $exception) {
            $this->gridHelper->addTranslatedFlash(
                'error',
                'Failed to cancel subscription "%subscription%".',
                null,
                [
                    '%subscription%' => $exception->getPaymentPlan()->getName(),
                ]
            );

            return new RedirectResponse($grid->generateMultiActionReturnUrl());
        }

        $count = count($ids);

        $this->gridHelper->addTranslatedFlash(
            'success',
            '%count% items will be deleted in the background within a few minutes.',
            $count,
            [
                '%count%' => $count,
            ]
        );

        return new RedirectResponse($grid->generateMultiActionReturnUrl());
    }

    protected function renderClientNameWithBadges(Client $client): string
    {
        return $this->twig->render(
            'client/components/view/grid_client_name_with_badges.html.twig',
            [
                'client' => $client,
            ]
        );
    }

    protected function renderClientBalance(Client $client, string $currencyCode): string
    {
        $balance = htmlspecialchars(
            $this->clientBalanceFormatter->getFormattedBalance($client, $currencyCode),
            ENT_QUOTES
        );

        if (! $client->hasOverdueInvoice()) {
            return $balance;
        }

        $span = Html::el(
            'span',
            [
                'class' => 'invoice--overdue',
                'data-tooltip' => $this->gridHelper->trans('Client has an overdue invoice.'),
            ]
        );

        return (string) $span->addText($balance);
    }

    protected function renderSiteDevice(string $connections): string
    {
        $connections = explode(ClientRepository::SITE_DEVICE_DELIMITER, $connections);
        if (! array_filter($connections)) {
            return BaseColumn::EMPTY_COLUMN;
        }

        $connectedTo = [];

        foreach ($connections as $connection) {
            $siteDevice = explode(ClientRepository::SITE_DEVICE_SEPARATOR, $connection, 2);
            if (count($siteDevice) !== 2) {
                continue;
            }

            $site = htmlspecialchars($siteDevice[0] ?? '', ENT_QUOTES);
            $device = htmlspecialchars($siteDevice[1] ?? '', ENT_QUOTES);
            $deviceTruncated = Strings::truncate($siteDevice[1], 16);
            $deviceEl = Html::el(
                'small',
                [
                    'data-tooltip' => Strings::length($device) !== Strings::length($deviceTruncated) ? $device : null,
                ]
            );
            $deviceEl->setText($deviceTruncated);

            $connectedTo[] = sprintf(
                '%s %s %s',
                $site,
                html_entity_decode('&ndash;'),
                $deviceEl
            );
        }

        return implode(', ', $connectedTo);
    }

    protected function exportCsvAction(Grid $grid): Response
    {
        $ids = $grid->getDoMultiActionIds() ?: $grid->getExportIds();
        if (! $ids) {
            $this->gridHelper->addTranslatedFlash('warning', 'There are no client logs to export.');

            return new RedirectResponse($grid->generateMultiActionReturnUrl());
        }

        $count = count($ids);
        $this->clientFacade->prepareCsvDownload(
            $this->gridHelper->transChoice(
                'Data of %count% clients in %filetype%',
                $count,
                [
                    '%count%' => $count,
                    '%filetype%' => 'CSV',
                ]
            ),
            $ids,
            $this->gridHelper->getUser()
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
}
