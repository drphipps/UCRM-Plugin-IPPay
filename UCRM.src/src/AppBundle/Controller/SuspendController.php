<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\DataProvider\ServiceDataProvider;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\EntityLog;
use AppBundle\Entity\NotificationTemplate;
use AppBundle\Entity\Option;
use AppBundle\Entity\Service;
use AppBundle\Entity\ServiceStopReason;
use AppBundle\Exception\PublicUrlGeneratorException;
use AppBundle\Facade\OnlinePaymentFacade;
use AppBundle\Facade\ServiceFacade;
use AppBundle\Facade\ServiceSuspensionFacade;
use AppBundle\Factory\Financial\PaymentTokenFactory;
use AppBundle\Form\Data\ServiceReactivateData;
use AppBundle\Form\ServiceReactivateType;
use AppBundle\Security\Permission;
use AppBundle\Service\ActionLogger;
use AppBundle\Service\ClientStatusUpdater;
use AppBundle\Service\Email\EmailEnqueuer;
use AppBundle\Service\Email\EmailLogger;
use AppBundle\Service\NotificationFactory;
use AppBundle\Service\PublicUrlGenerator;
use AppBundle\Service\ServiceStatusUpdater;
use AppBundle\Util\DateTimeFactory;
use AppBundle\Util\Message;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SuspendController extends BaseController
{
    /**
     * @Route("/suspended-service", name="suspended_service")
     *
     * @Permission("public")
     */
    public function showSuspendedServiceAction(Request $request): Response
    {
        if ($request->getMethod() !== 'GET') {
            try {
                $suspendedServiceUrl = $this->getSuspendedServiceUrl();

                return $this->redirect($suspendedServiceUrl);
            } catch (PublicUrlGeneratorException $exception) {
                return $this->redirectToRoute('suspended_service');
            }
        }

        $ipAddress = $request->getClientIp();
        $service = $this->getCurrentService($ipAddress);

        [$notification, $serviceCanBeReactivated, $serviceCanBePostponed] = $this->getServiceParams(
            $service,
            $ipAddress
        );

        $postponeUrl = null;
        if ($serviceCanBePostponed) {
            try {
                $postponeUrl = $this->getPostponeUrl();
            } catch (PublicUrlGeneratorException $exception) {
                $serviceCanBePostponed = false;
            }
        }

        $reactivateFormView = null;
        if ($serviceCanBeReactivated) {
            try {
                $reactivateData = new ServiceReactivateData();
                $reactivateForm = $this->getReactivateForm($service, $reactivateData);

                $reactivateForm->handleRequest($request);
                if ($reactivateForm->isSubmitted() && $reactivateForm->isValid()) {
                    $params = [
                        'endDate' => $reactivateData->endDate ? $reactivateData->endDate->format('Y-m-d') : null,
                    ];

                    return $this->redirect($this->getReactivateUrl($params));
                }
                $reactivateFormView = $reactivateForm->createView();
            } catch (PublicUrlGeneratorException $exception) {
                $serviceCanBeReactivated = false;
            }
        }

        $response = $this->render(
            'suspend/index.html.twig',
            [
                'service' => $service,
                'headline' => $notification->getSubject(),
                'body' => $notification->getBodyTemplate(),
                'serviceCanBePostponed' => $serviceCanBePostponed,
                'postponeUrl' => $postponeUrl,
                'serviceCanBeReactivated' => $serviceCanBeReactivated,
                'reactivateForm' => $reactivateFormView,
            ]
        );

        $this->addNoCacheHeaders($response);

        return $response;
    }

    /**
     * Don't add CSRF token to this method.
     *
     * @Route("/suspended-service/postpone", name="suspended_service_postpone")
     * @Method("GET")
     * @Permission("public")
     */
    public function postponeAction(Request $request): Response
    {
        if (! $this->getOption(Option::SUSPENSION_ENABLE_POSTPONE)) {
            throw $this->createAccessDeniedException('Suspension postponing is not enabled.');
        }

        $service = $this->getServiceByIp($request->getClientIp());

        try {
            $suspendedServiceUrl = $this->getSuspendedServiceUrl();
        } catch (PublicUrlGeneratorException $exception) {
            // This should never happen, as the postpone button is only displayed when public URL's can be generated.
            // It's just fallback to not show 500 when someone accesses the URL manually.
            throw $this->createAccessDeniedException();
        }

        if ($service->getSuspendPostponedByClient()) {
            $this->addTranslatedFlash('error', $this->trans('Service suspension is already postponed'));

            return $this->redirect($suspendedServiceUrl);
        }
        if ($service->getStopReason() &&
            $service->getStopReason()->getId() !== ServiceStopReason::STOP_REASON_OVERDUE_ID
        ) {
            $this->addTranslatedFlash('error', $this->trans('Service suspension cannot be postponed'));

            return $this->redirect($suspendedServiceUrl);
        }

        $this->get(ServiceSuspensionFacade::class)->postponeServiceByClient($service);
        $this->get(ServiceStatusUpdater::class)->updateServices();
        $this->get(ClientStatusUpdater::class)->update();

        $message['logMsg'] = [
            'message' => 'Suspension of service %s postponed.',
            'replacements' => $service->getName(),
        ];
        $logger = $this->container->get(ActionLogger::class);
        $logger->log($message, $this->getUser(), $service->getClient(), EntityLog::POSTPONE);

        if ($this->getOption(Option::NOTIFICATION_SERVICE_SUSPENSION_POSTPONED)) {
            $this->sendPostponeEmail($service);
        }

        return $this->redirect($this->getAwaitReactivationUrl());
    }

    /**
     * Don't add CSRF token to this method.
     *
     * @Route("/suspended-service/reactivate", name="suspended_service_reactivate")
     * @Method("GET")
     * @Permission("public")
     */
    public function reactivateAction(Request $request): Response
    {
        if (! $this->getOption(Option::CLIENT_ZONE_REACTIVATION)) {
            throw $this->createAccessDeniedException('Service reactivation is not enabled.');
        }

        $service = $this->getServiceByIp($request->getClientIp());

        try {
            $suspendedServiceUrl = $this->getSuspendedServiceUrl();
        } catch (PublicUrlGeneratorException $exception) {
            // This should never happen, as the reactivate button is only displayed when public URL's can be generated.
            // It's just fallback to not show 500 when someone accesses the URL manually.
            throw $this->createAccessDeniedException();
        }

        if ($service->getStatus() !== Service::STATUS_ENDED) {
            $this->addTranslatedFlash('error', $this->trans('Service cannot be reactivated.'));

            return $this->redirect($suspendedServiceUrl);
        }

        $endDateString = $request->get('endDate');
        $endDate = false;
        if ($endDateString) {
            try {
                $endDate = DateTimeFactory::createDate($endDateString);
            } catch (\InvalidArgumentException $iae) {
                $this->addTranslatedFlash('error', 'Service end date is invalid.');

                return $this->redirect($suspendedServiceUrl);
            }
            $now = new \DateTime();
            if ($endDate < $now) {
                $this->addTranslatedFlash('error', 'Service end date must be in the future.');

                return $this->redirect($suspendedServiceUrl);
            }
        }

        $serviceFacade = $this->get(ServiceFacade::class);
        $newService = $serviceFacade->createClonedService($service);
        if ($endDate) {
            $newService->setActiveTo($endDate);
        }
        $serviceFacade->handleObsolete($newService, $service);
        $this->get(ServiceStatusUpdater::class)->updateServices();
        $this->get(ClientStatusUpdater::class)->update();

        $message['logMsg'] = [
            'message' => 'Service %s reactivated.',
            'replacements' => $service->getName(),
        ];
        $logger = $this->container->get(ActionLogger::class);
        $logger->log($message, $this->getUser(), $service->getClient(), EntityLog::REACTIVATE);

        return $this->redirect($this->getAwaitReactivationUrl());
    }

    /**
     * @Route("/suspended-service/await-reactivation", name="suspended_service_await_reactivation")
     * @Method("GET")
     * @Permission("public")
     */
    public function awaitReactivationAction(Request $request): Response
    {
        $service = $this->getServiceByIp($request->getClientIp());

        if ($service->getStatus() !== Service::STATUS_ACTIVE) {
            $this->addTranslatedFlash('error', $this->trans('Service is not active.'));

            try {
                return $this->redirect(
                    $this->get(PublicUrlGenerator::class)->generate(
                        'suspended_service',
                        [],
                        false,
                        $this->getOption(Option::SERVER_SUSPEND_PORT)
                    )
                );
            } catch (PublicUrlGeneratorException $exception) {
                return $this->redirectToRoute('suspended_service');
            }
        }

        $paymentGatewayAvailable = $service->getClient()->getOrganization()->hasPaymentGateway($this->isSandbox());
        $minimumUnpaidAmount = $this->getOption(Option::SUSPENSION_MINIMUM_UNPAID_AMOUNT);
        $invoice = $this->get(ServiceDataProvider::class)->getFirstOverdueInvoice($service, $minimumUnpaidAmount);
        $token = null;
        if ($paymentGatewayAvailable && $invoice) {
            $token = $invoice->getPaymentToken();

            if (! $token) {
                $token = $this->get(PaymentTokenFactory::class)->create($invoice);
                $this->get(OnlinePaymentFacade::class)->handleCreatePaymentToken($token);
            }
        }
        $onlinePaymentUrl = null;
        $checkConnectionUrl = null;

        try {
            $checkConnectionUrl = $this->get(PublicUrlGenerator::class)->generate('suspended_service_check_connection');

            if ($token) {
                $onlinePaymentUrl = $this->get(PublicUrlGenerator::class)->generate(
                    'online_payment_pay',
                    [
                        'token' => $token->getToken(),
                    ]
                );
            }
        } catch (PublicUrlGeneratorException $exception) {
        }

        $response = $this->render(
            'suspend/await_reactivation.html.twig',
            [
                'service' => $service,
                'checkConnectionUrl' => $checkConnectionUrl,
                'onlinePaymentUrl' => $onlinePaymentUrl,
            ]
        );

        $this->addNoCacheHeaders($response);

        return $response;
    }

    /**
     * @Route("/suspended-service/check-connection", name="suspended_service_check_connection")
     * @Method("GET")
     * @Permission("public")
     */
    public function checkConnectionAction(): Response
    {
        $response = new JsonResponse(
            [
                'ucrm_connection_check' => 'ok',
            ]
        );
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET');
        $this->addNoCacheHeaders($response);

        return $response;
    }

    private function addNoCacheHeaders(Response $response): void
    {
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->addCacheControlDirective('max-age', 0);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->addCacheControlDirective('no-store', true);
    }

    private function sendPostponeEmail(Service $service): void
    {
        $failedRecipients = null;
        $exception = null;
        $client = $service->getClient();
        $notificationTemplate = $this->em
            ->find(NotificationTemplate::class, NotificationTemplate::CLIENT_POSTPONE_SUSPEND);

        $notification = $this->get(NotificationFactory::class)->create();
        $notification->setSubject($notificationTemplate->getSubject());
        $notification->setBodyTemplate($notificationTemplate->getBody());
        $notification->setService($service);
        $notification->setClient($client);

        $organization = $client->getOrganization();
        $billingEmails = $client->getBillingEmails();

        $message = new Message();
        $message->setClient($client);
        $message->setSender($this->getOption(Option::MAILER_SENDER_ADDRESS, $organization->getEmail()) ?: null);
        $message->setSubject($notification->getSubject());
        $message->setFrom($organization->getEmail(), $organization->getName());
        $message->setTo($billingEmails);
        $message->setBody(
            $this->renderView(
                'email/client/plain.html.twig',
                [
                    'body' => $notification->getBodyTemplate(),
                ]
            ),
            'text/html'
        );

        if (! $billingEmails) {
            $this->get(EmailLogger::class)->log(
                $message,
                'Email could not be sent, because client has no email set.',
                EmailLog::STATUS_ERROR
            );

            return;
        }

        $this->get(EmailEnqueuer::class)->enqueue($message, EmailEnqueuer::PRIORITY_MEDIUM);
    }

    private function getCurrentService(?string $ipAddress): ?Service
    {
        if ($ipAddress === null || ! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return null;
        }

        return $this->em->getRepository(Service::class)->getNotDeletedServiceByIp(ip2long($ipAddress));
    }

    private function canPostponeService(Service $service, ?ServiceStopReason $stopReason): bool
    {
        return $this->getOption(Option::SUSPENSION_ENABLE_POSTPONE)
            && ! $service->getSuspendPostponedByClient()
            && $this->getOption(Option::SERVER_IP)
            && (! $stopReason || $stopReason->getId() === ServiceStopReason::STOP_REASON_OVERDUE_ID);
    }

    private function hasCustomStopReason(?Service $service): bool
    {
        return $service
            && $service->getStopReason()
            && ! in_array($service->getStopReason()->getId(), ServiceStopReason::SYSTEM_REASONS, true);
    }

    private function isSuspendPrepared(Service $service): bool
    {
        return in_array(
            $service->getStatus(),
            [Service::STATUS_PREPARED, Service::STATUS_PREPARED_BLOCKED],
            true
        );
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    private function getServiceParams(?Service $service, string $ipAddress): array
    {
        $serviceCanBePostponed = false;
        $serviceCanBeReactivated = false;
        $customStopReason = $this->hasCustomStopReason($service);

        $notification = $this->get(NotificationFactory::class)->create();

        if ($service) {
            if ($service->getStatus() === Service::STATUS_ENDED) {
                $template = $this->em->find(NotificationTemplate::class, NotificationTemplate::SUSPEND_TERMINATED);

                $notification->setService($service);
                $notification->setClient($service->getClient());

                $serviceCanBeReactivated = $this->getOption(Option::CLIENT_ZONE_REACTIVATION);
            } elseif ($this->isSuspendPrepared($service)) {
                $template = $this->em->find(NotificationTemplate::class, NotificationTemplate::SUSPEND_PREPARED);

                $notification->setService($service);
                $notification->setClient($service->getClient());
            } elseif ($customStopReason) {
                $template = $this->em->find(NotificationTemplate::class, NotificationTemplate::SUSPEND_CUSTOM_REASON);

                $notification->setService($service);
                $notification->setClient($service->getClient());
                $notification->addReplacement('%SERVICE_STOP_REASON%', $service->getStopReason()->getName());
                $notification->addReplacement('%CLIENT_IP%', $ipAddress);
            } else {
                $template = $this->em->find(NotificationTemplate::class, NotificationTemplate::SUSPEND_RECOGNIZED);

                $notification->setService($service);
                $notification->setClient($service->getClient());
                $stopReason = $service->getStopReason();
                if ($stopReason) {
                    $reason = $this->trans($stopReason->getName(), [], 'service_stop_reason');
                } else {
                    $reason = '';
                }
                $notification->addReplacement('%SERVICE_STOP_REASON%', $reason);
                $notification->addReplacement('%CLIENT_IP%', $ipAddress);

                $serviceCanBePostponed = $this->canPostponeService($service, $stopReason);
            }
        } else {
            $template = $this->em->find(NotificationTemplate::class, NotificationTemplate::SUSPEND_ANONYMOUS);
        }

        $notification->setSubject($template->getSubject());
        $notification->setBodyTemplate($template->getBody());

        return [$notification, $serviceCanBeReactivated, $serviceCanBePostponed];
    }

    private function getSuspendedServiceUrl(): string
    {
        return $this->get(PublicUrlGenerator::class)->generate(
            'suspended_service',
            [],
            false,
            $this->getOption(Option::SERVER_SUSPEND_PORT)
        );
    }

    private function getPostponeUrl(): string
    {
        return $this->get(PublicUrlGenerator::class)->generate(
            'suspended_service_postpone',
            [],
            false,
            $this->getOption(Option::SERVER_SUSPEND_PORT)
        );
    }

    private function getReactivateForm(
        ?Service $service,
        ServiceReactivateData $reactivateData
    ): \Symfony\Component\Form\FormInterface {
        $formFactory = $this->get('form.factory');

        return $formFactory
            ->createNamedBuilder(
                'reactivateForm',
                ServiceReactivateType::class,
                $reactivateData,
                [
                    'service' => $service,
                ]
            )->setMethod('GET')
            ->getForm();
    }

    private function getReactivateUrl($params): string
    {
        return $this->get(PublicUrlGenerator::class)->generate(
            'suspended_service_reactivate',
            $params,
            false,
            $this->getOption(Option::SERVER_SUSPEND_PORT)
        );
    }

    private function getAwaitReactivationUrl(): string
    {
        return $this->get(PublicUrlGenerator::class)->generate(
            'suspended_service_await_reactivation',
            [],
            false,
            $this->getOption(Option::SERVER_SUSPEND_PORT)
        );
    }

    /**
     * @throws AccessDeniedException
     */
    private function getServiceByIp(string $ip): Service
    {
        $service = $this->getCurrentService($ip);
        if (! $service) {
            throw $this->createAccessDeniedException('Service could not be recognized by IP.');
        }

        return $service;
    }
}
