<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Controller\ClientZone;

use ApiBundle\Request\AccountStatementRequest;
use AppBundle\Component\Map\ServicesMapProvider;
use AppBundle\DataProvider\AccountStatementDataProvider;
use AppBundle\DataProvider\PaymentTokenDataProvider;
use AppBundle\DataProvider\ServiceDataProvider;
use AppBundle\Entity\ClientBankAccount;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\Option;
use AppBundle\Entity\Organization;
use AppBundle\Exception\TemplateRenderException;
use AppBundle\Facade\ClientBankAccountFacade;
use AppBundle\Form\ChangePasswordType;
use AppBundle\Form\ClientBankAccountType;
use AppBundle\Form\Data\ChangePasswordData;
use AppBundle\Form\Data\StripeAchVerifyData;
use AppBundle\Form\StripeAchVerifyType;
use AppBundle\Security\Permission;
use AppBundle\Service\ActionLogger;
use AppBundle\Service\Client\ClientBalanceFormatter;
use AppBundle\Service\DownloadResponseFactory;
use AppBundle\Service\Financial\FinancialTemplateRenderer;
use AppBundle\Util\Helpers;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use SchedulingBundle\DataProvider\JobDataProvider;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Request\JobCollectionRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Stripe\Error;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/client-zone")
 */
class ClientController extends BaseController
{
    /**
     * @Route("", name="client_zone_client_index")
     * @Method("GET")
     * @Permission("guest")
     */
    public function indexAction(): Response
    {
        $client = $this->getClient();
        $services = $client->getNotDeletedServices()->toArray();
        $organizationCount = $this->em->getRepository(Organization::class)->getCount();
        $invoices = $this->em->getRepository(Invoice::class)->getClientUnpaidInvoices($client);

        $linkedSubscriptionPossible = $this->getOption(Option::SUBSCRIPTIONS_ENABLED_LINKED)
            && $client->getOrganization()->hasPaymentProviderSupportingAutopay($this->isSandbox())
            && $this->get(ServiceDataProvider::class)->getServicesForLinkedSubscriptions($client);
        $customSubscriptionPossible = $this->getOption(Option::SUBSCRIPTIONS_ENABLED_CUSTOM)
            && $client->getOrganization()->hasPaymentGateway($this->isSandbox());
        $paymentPlans = null;
        if ($linkedSubscriptionPossible || $customSubscriptionPossible) {
            $paymentPlans = $client->getActivePaymentPlans();
        }

        $jobCollectionRequest = new JobCollectionRequest();
        $jobCollectionRequest->client = $client;
        $jobCollectionRequest->statuses = [Job::STATUS_OPEN, Job::STATUS_IN_PROGRESS];
        $jobCollectionRequest->public = true;

        return $this->render(
            'client_zone/client/index.html.twig',
            [
                'client' => $client,
                'organizationCount' => $organizationCount,
                'services' => $services,
                'servicesMap' => $this->get(ServicesMapProvider::class)->getData($services, false),
                'invoices' => $invoices,
                'credit' => $client->getAccountStandingsCredit(),
                'outstanding' => $client->getAccountStandingsOutstanding(),
                'balance' => $this->get(ClientBalanceFormatter::class)->getFormattedBalanceRaw($client->getBalance()),
                'paymentPlans' => $paymentPlans,
                'paymentGatewayAvailable' => $client->getOrganization()->hasPaymentGateway($this->isSandbox()),
                'hasStripe' => $client->getOrganization()->hasStripe($this->isSandbox()),
                'hasStripeAch' => $client->getOrganization()->hasStripeAch($this->isSandbox()),
                'jobsByDate' => $this->em->getRepository(Job::class)->getByClientByDate($client, 5, null, true),
                'jobs' => $this->get(JobDataProvider::class)->getAllJobs($jobCollectionRequest),
                'invoiceIdsWithPendingPayments' => $this->get(
                    PaymentTokenDataProvider::class
                )->getInvoiceIdsWithPendingPayments($client),
                'linkedSubscriptionPossible' => $linkedSubscriptionPossible,
                'customSubscriptionPossible' => $customSubscriptionPossible,
            ]
        );
    }

    /**
     * @Route("/change-password", name="client_change_password")
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function changePasswordAction(Request $request): Response
    {
        $user = $this->getUser();

        $changePasswordModel = new ChangePasswordData();
        $changePasswordModel->user = $user;
        $url = $this->generateUrl('client_change_password');
        $form = $this->createForm(ChangePasswordType::class, $changePasswordModel, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (! Helpers::isDemo()) {
                $password = $this->get('security.password_encoder')->encodePassword(
                    $user,
                    $changePasswordModel->newPassword
                );

                $user->setPassword($password);

                $this->em->persist($user);
                $this->em->flush();

                $message['logMsg'] = [
                    'message' => 'User changed his password.',
                    'replacements' => '',
                ];

                $this->get(ActionLogger::class)->log($message, $user, $user->getClient(), EntityLog::PASSWORD_CHANGE);
            }

            return new JsonResponse(
                [
                    'status' => 1,
                    'message' => 'Password has been changed.',
                ]
            );
        }

        return $this->render(
            'user/change_password.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/bank-account/new", name="client_zone_bank_account_number_add", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function addClientBankAccountNumberAction(Request $request): Response
    {
        $client = $this->getClient();

        $bankAccount = new ClientBankAccount();
        $bankAccount->setClient($client);

        $url = $this->generateUrl('client_zone_bank_account_number_add', ['id' => $client->getId()]);
        $form = $this->createForm(ClientBankAccountType::class, $bankAccount, ['action' => $url]);
        $form->handleRequest($request);

        $hasStripeAch = $client->getOrganization()->hasStripeAch($this->isSandbox());

        if (! $hasStripeAch) {
            throw $this->createNotFoundException();
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $bankAccountFacade = $this->get(ClientBankAccountFacade::class);
            $bankAccountFacade->handleNew($bankAccount);

            $this->invalidateTemplate(
                'client-bank-accounts',
                'client/components/view/bank_accounts.html.twig',
                [
                    'client' => $client,
                    'hasStripeAch' => $hasStripeAch,
                ]
            );

            $this->invalidateTemplate(
                'stripe-ach',
                'client_zone/client/components/view/stripe_ach.html.twig',
                [
                    'client' => $client,
                ]
            );

            $this->addTranslatedFlash('success', 'Bank account has been created.');

            return $this->createAjaxResponse();
        }

        return $this->render(
            'client/components/edit/bank_account.html.twig',
            [
                'form' => $form->createView(),
                'hasStripeAch' => $hasStripeAch,
            ]
        );
    }

    /**
     * @Route("/bank-account/{id}/stripe-add-bank-account-token", name="client_zone_bank_account_stripe_create_token", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("guest")
     * @CsrfToken()
     */
    public function addStripeBankAccountToken(ClientBankAccount $bankAccount): Response
    {
        try {
            $this->get(ClientBankAccountFacade::class)->createStripeBankAccount($bankAccount, $this->isSandbox());
            $this->addTranslatedFlash('success', 'Bank account has been connected.');
        } catch (Error\Permission | Error\Authentication | Error\InvalidRequest | \InvalidArgumentException $exception) {
            $this->addTranslatedFlash('error', $exception->getMessage());
        }

        $this->invalidateTemplate(
            'stripe-ach',
            'client_zone/client/components/view/stripe_ach.html.twig',
            [
                'client' => $bankAccount->getClient(),
            ]
        );

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/bank-account/{id}/stripe-bank-account-verify", name="client_zone_bank_account_stripe_verify", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("guest")
     */
    public function verifyStripeBankAccount(ClientBankAccount $bankAccount, Request $request): Response
    {
        $data = new StripeAchVerifyData();
        $url = $this->generateUrl('client_zone_bank_account_stripe_verify', ['id' => $bankAccount->getId()]);
        $form = $this->createForm(StripeAchVerifyType::class, $data, ['action' => $url]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $isStripeError = false;
            try {
                $this->get(ClientBankAccountFacade::class)->verifyStripeBankAccount(
                    $bankAccount,
                    $this->isSandbox(),
                    $data->firstDeposit,
                    $data->secondDeposit
                );
            } catch (Error\Permission | Error\Authentication | Error\InvalidRequest $exception) {
                $isStripeError = true;
                $this->addTranslatedFlash('error', $exception->getMessage());
            } catch (Error\Card $exception) {
                $form->get('firstDeposit')->addError(
                    new FormError(
                        'The values provided do not match the values of the two micro-deposits that were sent. Or the limit of verification attempts was exceeded (max. 10 failed verification attempts).'
                    )
                );
            }

            if (! $isStripeError && $form->isValid()) {
                $this->addTranslatedFlash('success', 'Bank account has been verified');
            }

            if ($isStripeError || $form->isValid()) {
                $this->invalidateTemplate(
                    'stripe-ach',
                    'client_zone/client/components/view/stripe_ach.html.twig',
                    [
                        'client' => $bankAccount->getClient(),
                    ]
                );

                return $this->createAjaxResponse();
            }
        }

        return $this->render(
            'client_zone/client/stripe_ach_verify_modal.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/account-statement", name="client_zone_show_account_statement")
     * @Method({"GET"})
     * @Permission("guest")
     */
    public function accountStatementShowAction(): Response
    {
        $client = $this->getClient();

        $ascr = new AccountStatementRequest($client);

        return $this->render(
            'client_zone/client/show_account_statement.html.twig',
            [
                'client' => $client,
                'accountStatement' => $this->get(AccountStatementDataProvider::class)->getAccountStatement($ascr),
            ]
        );
    }

    /**
     * @Route("/account-statement/pdf", name="client_zone_show_account_statement_pdf")
     * @Method({"GET"})
     * @Permission("guest")
     */
    public function accountStatementPdfHtmlAction(): Response
    {
        $client = $this->getClient();

        $request = new AccountStatementRequest($client);

        $accountStatementTemplate = $client->getOrganization()->getAccountStatementTemplate();
        if (! $accountStatementTemplate) {
            throw $this->createNotFoundException();
        }

        $pdf = null;
        try {
            $pdf = $this->get(FinancialTemplateRenderer::class)->getAccountStatementPdf(
                $this->get(AccountStatementDataProvider::class)->getAccountStatement($request),
                $accountStatementTemplate
            );
        } catch (TemplateRenderException | \Dompdf\Exception $exception) {
            throw $this->createNotFoundException();
        }

        $responseFactory = new DownloadResponseFactory();

        return $responseFactory->createFromContent(
            $pdf,
            'accountStatement_' . $client->getId(),
            'pdf',
            'application/pdf',
            strlen($pdf)
        );
    }
}
