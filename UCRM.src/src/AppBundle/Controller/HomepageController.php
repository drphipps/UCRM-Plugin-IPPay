<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\NetFlow\NetFlowChartDataProvider;
use AppBundle\Component\NetFlow\TopTrafficChartDataProvider;
use AppBundle\Component\Ping\DeviceOutageProvider;
use AppBundle\DataProvider\DashboardDataProvider;
use AppBundle\Entity\EmailLog;
use AppBundle\Entity\Financial\Invoice;
use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Entity\Payment;
use AppBundle\Entity\User;
use AppBundle\Facade\EmailLogFacade;
use AppBundle\Facade\IpAccountingFacade;
use AppBundle\Facade\ServiceDeviceFacade;
use AppBundle\Grid\EmailLog\FailedEmailLogGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Service\Options;
use AppBundle\Util\DiskUsage;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use SchedulingBundle\Entity\Job;
use SchedulingBundle\Security\SchedulingPermissions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TicketingBundle\Controller\TicketController;
use TicketingBundle\DataProvider\TicketDataProvider;

class HomepageController extends BaseController
{
    /**
     * @Route("/", name="homepage")
     *
     * @Permission("guest")
     *
     * @throws \Exception
     */
    public function indexAction(Request $request): Response
    {
        if ($this->isGranted(User::ROLE_CLIENT)) {
            return $this->redirectToRoute('client_zone_client_index');
        }

        $unmatchedPayments = $this->isPermissionGranted(Permission::VIEW, PaymentController::class)
            ? $this->em->getRepository(Payment::class)->getUnmatchedPayments(3)
            : [];

        if (
            $this->isPermissionGranted(Permission::VIEW, OutageController::class)
            || $this->isPermissionGranted(Permission::VIEW, UnknownDevicesController::class)
        ) {
            $outageProvider = $this->get(DeviceOutageProvider::class);
            $networkDeviceOutageCount = $outageProvider->getNetworkOngoingCount();
            $serviceDeviceOutageCount = $outageProvider->getServiceOngoingCount();

            $netFlowUnknownCount = $this->get(IpAccountingFacade::class)->getCount();
            $serviceDeviceUnknownCount = $this->get(ServiceDeviceFacade::class)->getUnknownDevicesCount();
        }

        if ($this->isPermissionGranted(Permission::VIEW, ReportDataUsageController::class)) {
            $trafficChartDataProvider = $this->get(NetFlowChartDataProvider::class);
            $trafficChartData = $trafficChartDataProvider->getChartDataForDashboard();

            $lastDayTopDownload = $this->get(TopTrafficChartDataProvider::class)->getChartData(
                TopTrafficChartDataProvider::TYPE_DOWNLOAD,
                TopTrafficChartDataProvider::PERIOD_TODAY
            );
        }

        $lastOverdueInvoices = $this->isPermissionGranted(Permission::VIEW, InvoiceController::class)
            ? $this->em->getRepository(Invoice::class)->getLastOverdueInvoices(3)
            : [];

        $user = $this->getUser();
        $jobRepository = $this->em->getRepository(Job::class);
        $viewMyJobsGranted = $this->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_MY);
        $viewAllJobsGranted = $this->isGranted(Permission::VIEW, SchedulingPermissions::JOBS_ALL);
        $myJobs = $viewMyJobsGranted
            ? $jobRepository->getByUserByDate($user, 5)
            : [];
        $jobsQueue = $viewAllJobsGranted
            ? $jobRepository->getQueue(6)
            : [];

        $ticketDataProvider = $this->get(TicketDataProvider::class);
        $viewTicketsPermissionGranted = $this->isPermissionGranted(Permission::VIEW, TicketController::class);
        $myTickets = $viewTicketsPermissionGranted
            ? $ticketDataProvider->getActiveByAssignedUser($this->getUser(), 5)
            : [];
        $allTickets = $viewTicketsPermissionGranted
            ? $ticketDataProvider->getActiveAll(5)
            : [];

        $dashboardDataProvider = $this->get(DashboardDataProvider::class);

        // No disk usage for UAS, it's impossible to get correct numbers.
        if (! (bool) $this->get(Options::class)->getGeneral(General::UAS_INSTALLATION)) {
            $diskUsage = DiskUsage::get();
        }

        $lastFailedEmailsGrid = $this->isPermissionGranted(Permission::VIEW, EmailLogController::class)
            ? $this->get(FailedEmailLogGridFactory::class)->create()
            : null;
        if ($lastFailedEmailsGrid && $parameters = $lastFailedEmailsGrid->processAjaxRequest($request)) {
            $this->invalidateTemplate(
                'failed-emails-log__footer',
                'homepage/components/emails_footer.html.twig',
                [
                    'lastFailedEmailsGrid' => $lastFailedEmailsGrid,
                ]
            );

            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'homepage/index.html.twig',
            [
                'overviewData' => $dashboardDataProvider->getOverview(),
                'user' => $user,
                'unmatchedPayments' => $unmatchedPayments,
                'checklist' => $this->getChecklist(),
                'networkDeviceOutageCount' => $networkDeviceOutageCount ?? 0,
                'serviceDeviceOutageCount' => $serviceDeviceOutageCount ?? 0,
                'netFlowUnknownCount' => $netFlowUnknownCount ?? 0,
                'serviceDeviceUnknownCount' => $serviceDeviceUnknownCount ?? 0,
                'lastOverdueInvoices' => $lastOverdueInvoices,
                'lastDayTopDownload' => $lastDayTopDownload ?? null,
                'trafficChartData' => $trafficChartData ?? null,
                'diskUsage' => $diskUsage ?? null,
                'myJobs' => $myJobs,
                'jobsQueue' => $jobsQueue,
                'viewAllJobsGranted' => $viewAllJobsGranted,
                'viewMyJobsGranted' => $viewMyJobsGranted,
                'myTickets' => $myTickets,
                'allTickets' => $allTickets,
                'onboarding' => $dashboardDataProvider->getOnboarding(),
                'lastFailedEmailsGrid' => $lastFailedEmailsGrid,
                'hideAction' => false,
            ]
        );
    }

    /**
     * @Route(
     *     "/top-traffic-chart/{type}/{period}",
     *     name="homepage_top_traffic_chart",
     *     requirements={
     *         "type": "download|upload",
     *         "period": "day|week|month"
     *     },
     *     options={"expose": true}
     * )
     * @Method("GET")
     * @Permission("guest")
     */
    public function getTopTrafficChartAction(string $type, string $period): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::VIEW, ReportDataUsageController::class);

        $chartData = $this->get(TopTrafficChartDataProvider::class)->getChartData($type, $period);

        $this->invalidateTemplate(
            'top-traffic-chart-container',
            'homepage/components/top_traffic_chart.html.twig',
            [
                'chartData' => $chartData,
                'isUpload' => $type === TopTrafficChartDataProvider::TYPE_UPLOAD,
                'period' => $period,
            ]
        );

        $this->invalidateTemplate(
            'top-traffic-chart-title',
            'homepage/components/top_traffic_title.html.twig',
            [
                'isUpload' => $type === TopTrafficChartDataProvider::TYPE_UPLOAD,
            ]
        );

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/hide-failed-email/{id}", name="hide_failed_email")
     * @Method("GET")
     * @Permission("guest")
     */
    public function hideFailedEmailAction(EmailLog $emailLog): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, EmailLogController::class);

        $discarded = $this->get(EmailLogFacade::class)->setDiscardedStatus($emailLog->getId());

        $this->addTranslatedFlash('success', '%count% emails have been hidden.', $discarded);

        $this->invalidateTemplate(
            'failed-emails',
            'homepage/components/emails.html.twig',
            [
                'lastFailedEmailsGrid' => $this->get(FailedEmailLogGridFactory::class)->create(),
                'hideAction' => true,
            ]
        );

        return $this->createAjaxResponse();
    }

    /**
     * @Route("/hide-failed-emails", name="hide_failed_emails")
     * @Method("GET")
     * @Permission("guest")
     * @CsrfToken()
     */
    public function hideFailedEmailsAction(): Response
    {
        $this->denyAccessUnlessPermissionGranted(Permission::EDIT, EmailLogController::class);

        $discarded = $this->get(EmailLogFacade::class)->setAllDiscardedStatus();

        $this->addTranslatedFlash('success', '%count% emails have been hidden.', $discarded);

        $this->invalidateTemplate(
            'failed-emails',
            'homepage/components/emails.html.twig',
            [
                'lastFailedEmailsGrid' => $this->get(FailedEmailLogGridFactory::class)->create(),
                'hideAction' => true,
            ]
        );

        return $this->createAjaxResponse();
    }

    private function getChecklist(): array
    {
        $checklist = [
            [
                'label' => 'Server IP or domain name not configured.',
                'link' => $this->generateUrl('setting_application_edit'),
                'done' => $this->getOption(Option::SERVER_IP) || $this->getOption(Option::SERVER_FQDN),
            ],
        ];

        if (count(array_filter(array_column($checklist, 'done'))) === count($checklist)) {
            $checklist = [
                [
                    'label' => 'All systems operational.',
                    'done' => true,
                    'classes' => [
                        'all-operational',
                    ],
                ],
            ];
        }

        return $checklist;
    }
}
