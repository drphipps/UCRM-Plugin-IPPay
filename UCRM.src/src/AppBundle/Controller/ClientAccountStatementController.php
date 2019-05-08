<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Service\TimePeriod;
use AppBundle\DataProvider\AccountStatementDataProvider;
use AppBundle\DataProvider\InvoiceDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Form\AccountStatementFilterType;
use AppBundle\Form\Data\AccountStatementFilterData;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionControllerName;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Service\Financial\FinancialTemplateRenderer;
use AppBundle\Util\DateTimeImmutableFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client")
 * @PermissionControllerName(ClientController::class)
 */
class ClientAccountStatementController extends BaseController
{
    /**
     * @Route("/{id}/account-statement", name="client_show_account_statement")
     * @Method({"GET"})
     * @Permission("view")
     */
    public function accountStatementShowAction(Request $request, Client $client): Response
    {
        if ($client->getIsLead()) {
            throw $this->createNotFoundException();
        }

        $accountStatementFilterData = new AccountStatementFilterData();
        $form = $this->createForm(
            AccountStatementFilterType::class,
            $accountStatementFilterData,
            [
                'method' => 'GET',
                'csrf_protection' => false, // no need for CSRF as it's only GET data and the URL is prettier without it
                'action' => $this->generateUrl(
                    'client_show_account_statement',
                    [
                        'id' => $client->getId(),
                    ]
                ),
            ]
        );

        $form->handleRequest($request);

        $accountStatementFilterData = $form->getData() ?? new AccountStatementFilterData();
        try {
            $timePeriod = AccountStatementFilterData::getTimePeriod($accountStatementFilterData->dateFilter);
        } catch (\InvalidArgumentException $exception) {
            $this->addTranslatedFlash('error', 'Date is not in valid format.');

            if ($request->isXmlHttpRequest()) {
                return $this->createAjaxResponse();
            }

            $timePeriod = TimePeriod::allTime();
        }
        $accountStatement = $this->get(AccountStatementDataProvider::class)
            ->getAccountStatementByTimePeriod($client, $timePeriod);

        if ($request->isXmlHttpRequest() && $form->isSubmitted() && $form->isValid()) {
            $this->invalidateTemplate(
                'account-statement-data',
                'client/components/view/account_statement_data.html.twig',
                [
                    'client' => $client,
                    'accountStatement' => $accountStatement,
                    'startDate' => $timePeriod->startDate,
                    'endDate' => $timePeriod->endDate,
                ]
            );

            $this->invalidateTemplate(
                'account-statement-download',
                'client/components/view/account_statement_download.html.twig',
                [
                    'client' => $client,
                    'startDate' => $timePeriod->startDate,
                    'endDate' => $timePeriod->endDate,
                ]
            );

            return $this->createAjaxResponse(
                [
                    'url' => $request->getUri(),
                ]
            );
        }

        return $this->render(
            'client/show_account_statement.html.twig',
            [
                'client' => $client,
                'form' => $form->createView(),
                'accountStatement' => $accountStatement,
                'startDate' => $timePeriod->startDate,
                'endDate' => $timePeriod->endDate,
                'showProformaInvoices' => $this->get(InvoiceDataProvider::class)->showProformaInvoices(),
            ]
        );
    }

    /**
     * @Route("/{id}/account-statement-pdf/{from}/{to}", name="client_show_account_statement_pdf", defaults={"from"=null,"to"=null})
     * @Method({"GET"})
     * @Permission("view")
     */
    public function accountStatementPdfHtmlAction(Client $client, string $from = null, string $to = null): Response
    {
        if ($client->getIsLead()) {
            throw $this->createNotFoundException();
        }

        $timePeriod = new TimePeriod();
        try {
            if ($from) {
                $timePeriod->startDate = DateTimeImmutableFactory::createDate($from);
            }
            if ($to) {
                $timePeriod->endDate = DateTimeImmutableFactory::createDate($to)->modify('+1 day midnight -1 second');
            }
        } catch (\InvalidArgumentException $exception) {
            $this->addTranslatedFlash('error', 'Date is not in valid format.');

            return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
        }
        $accountStatement = $this->get(AccountStatementDataProvider::class)
            ->getAccountStatementByTimePeriod($client, $timePeriod);
        $accountStatementTemplate = $client->getOrganization()->getAccountStatementTemplate();
        if (! $accountStatementTemplate) {
            throw $this->createNotFoundException();
        }

        $dateRange = '';
        if ($timePeriod->startDate) {
            $dateRange .= $timePeriod->startDate->format('Y-m-d');
        }
        if ($timePeriod->endDate) {
            $dateRange .= '-' . $timePeriod->endDate->format('Y-m-d');
        }

        try {
            $pdf = $this->get(FinancialTemplateRenderer::class)->getAccountStatementPdf(
                $accountStatement,
                $accountStatementTemplate
            );
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            throw $this->createNotFoundException();
        }

        $responseFactory = new DownloadResponseFactory();

        if ($dateRange) {
            // avoid automatic timestamp
            $fileExtension = null;
            $dateRange .= '.pdf';
        } else {
            $fileExtension = 'pdf';
        }

        return $responseFactory->createFromContent(
            $pdf,
            'accountStatement_' . $client->getId() . $dateRange,
            $fileExtension,
            'application/pdf',
            strlen($pdf)
        );
    }
}
