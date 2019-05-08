<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Component\Elastic\Annotation\Searchable;
use AppBundle\Entity\UserGroup;
use AppBundle\Entity\UserGroupPermission;
use AppBundle\Entity\UserGroupSpecialPermission;
use AppBundle\Facade\UserGroupFacade;
use AppBundle\Form\UserGroupType;
use AppBundle\Grid\UserGroup\UserGroupGridFactory;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionNames;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Genedys\CsrfRouteBundle\Annotation\CsrfToken;
use SchedulingBundle\Security\SchedulingPermissions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TicketingBundle\Controller\TicketController;

/**
 * @Route("/system/security/user-groups")
 */
class UserGroupController extends BaseController
{
    const MODULE_PERMISSION = 'module';
    const SPECIAL_PERMISSION = 'special';

    /**
     * @var array
     */
    private $moduleIndexes = [];

    /**
     * @var array
     */
    private $specialModuleIndexes = [];

    /**
     * @Route("", name="user_group_index")
     * @Method("GET")
     * @Permission("view")
     * @Searchable(heading="Groups &amp; permissions", path="System -> Users -> Groups &amp; permissions")
     */
    public function indexAction(Request $request): Response
    {
        $grid = $this->get(UserGroupGridFactory::class)->create();
        if ($parameters = $grid->processAjaxRequest($request)) {
            return $this->createAjaxResponse($parameters);
        }

        return $this->render(
            'user_group/index.html.twig',
            [
                'grid' => $grid,
            ]
        );
    }

    /**
     * @Route("/new", name="user_group_new")
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function newAction(Request $request): Response
    {
        return $this->handleNewEditAction($request);
    }

    /**
     * @Route("/{id}/edit", name="user_group_edit", requirements={"id": "\d+"})
     * @Method({"GET", "POST"})
     * @Permission("edit")
     */
    public function editAction(Request $request, UserGroup $userGroup): Response
    {
        return $this->handleNewEditAction($request, $userGroup);
    }

    /**
     * @Route("/{id}", name="user_group_show", requirements={"id": "\d+"})
     * @Method("GET")
     * @Permission("view")
     */
    public function showAction(UserGroup $userGroup): Response
    {
        $this->initPermissions($userGroup);

        return $this->render(
            'user_group/show.html.twig',
            [
                'userGroup' => $userGroup,
                'moduleNames' => PermissionNames::PERMISSION_HUMAN_NAMES,
                'moduleGroups' => $this->getModulePermissionGroups(),
                'moduleIndexes' => $this->moduleIndexes,
                'specialPermissionNames' => PermissionNames::SPECIAL_PERMISSIONS_HUMAN_NAMES,
                'specialModuleIndexes' => $this->specialModuleIndexes,
            ]
        );
    }

    /**
     * @Route("/{id}/delete", name="user_group_delete", requirements={"id": "\d+"})
     * @Method("GET")
     * @CsrfToken()
     * @Permission("edit")
     */
    public function deleteAction(UserGroup $userGroup): Response
    {
        if ($userGroup->getId() < UserGroup::USER_GROUP_MAX_SYSTEM_ID) {
            $this->addTranslatedFlash('error', 'This user group cannot be deleted.');
        } else {
            try {
                $this->get(UserGroupFacade::class)->handleDelete($userGroup);

                $this->addTranslatedFlash('success', 'User group has been deleted.');
            } catch (ForeignKeyConstraintViolationException  $e) {
                $this->addTranslatedFlash('error', 'Cannot be deleted. Item is used.');
            }
        }

        return $this->redirectToRoute('user_group_index');
    }

    private function handleNewEditAction(Request $request, UserGroup $userGroup = null): Response
    {
        $isEdit = true;

        if (null === $userGroup) {
            $userGroup = new UserGroup();
            $isEdit = false;
        }

        $this->initPermissions($userGroup);
        $editForm = $this->createForm(UserGroupType::class, $userGroup);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->get(UserGroupFacade::class)->handleUpdate($userGroup);

            if ($isEdit) {
                $this->addTranslatedFlash('success', 'User group has been saved.');
            } else {
                $this->addTranslatedFlash('success', 'User group has been created.');
            }

            return $this->redirectToRoute(
                'user_group_show',
                [
                    'id' => $userGroup->getId(),
                ]
            );
        }

        return $this->render(
            $isEdit ? 'user_group/edit.html.twig' : 'user_group/new.html.twig',
            [
                'userGroup' => $userGroup,
                'form' => $editForm->createView(),
                'moduleNames' => PermissionNames::PERMISSION_HUMAN_NAMES,
                'moduleGroups' => $this->getModulePermissionGroups(),
                'moduleIndexes' => $this->moduleIndexes,
                'specialPermissionNames' => PermissionNames::SPECIAL_PERMISSIONS_HUMAN_NAMES,
                'specialModuleIndexes' => $this->specialModuleIndexes,
                'isEdit' => $isEdit,
            ]
        );
    }

    private function initPermissions(UserGroup $userGroup): void
    {
        // find all modules which are available but not set in appGroup
        $used = [];
        foreach ($userGroup->getPermissions() as $index => $permission) {
            $used[] = $permission->getModuleName();
            $this->moduleIndexes[$permission->getModuleName()] = $index;
        }

        foreach ($userGroup->getSpecialPermissions() as $index => $specialPermission) {
            $used[] = $specialPermission->getModuleName();
            $this->specialModuleIndexes[$specialPermission->getModuleName()] = $index;
        }

        $modules = array_merge(
            UserGroup::PERMISSION_MODULES,
            SchedulingPermissions::PERMISSION_SUBJECTS
        );
        foreach ($modules as $name) {
            if (! in_array($name, $used, true)) {
                $perm = new UserGroupPermission();
                $perm->setModuleName($name);
                $userGroup->addPermission($perm);

                $this->moduleIndexes[$name] = $userGroup->getPermissions()->indexOf($perm);
            }
        }

        foreach (UserGroup::SPECIAL_PERMISSIONS as $permissionName) {
            if (! in_array($permissionName, $used, true)) {
                $perm = new UserGroupSpecialPermission();
                $perm->setModuleName($permissionName);
                $userGroup->addSpecialPermission($perm);

                $this->specialModuleIndexes[$permissionName] = $userGroup->getSpecialPermissions()->indexOf($perm);
            }
        }
    }

    /**
     * Returns module permission groups. Used only for front-end.
     *
     * NEW CONTROLLER NEEDS TO BE ADDED TO 3 PLACES!
     *
     * @see https://ubiquiti.atlassian.net/wiki/display/ABR/Technical+documentation#Technicaldocumentation-Security
     * @see PermissionNames::PERMISSION_HUMAN_NAMES
     * @see PermissionNames::PERMISSION_SYSTEM_NAMES
     * @see UserGroup::PERMISSION_MODULES
     */
    private function getModulePermissionGroups(): array
    {
        return [
            'Clients' => [
                ClientController::class => self::MODULE_PERMISSION,
                DocumentController::class => self::MODULE_PERMISSION,
                ServiceController::class => self::MODULE_PERMISSION,
            ],
            'Billing' => [
                InvoiceController::class => self::MODULE_PERMISSION,
                QuoteController::class => self::MODULE_PERMISSION,
                PaymentController::class => self::MODULE_PERMISSION,
                RefundController::class => self::MODULE_PERMISSION,
                TaxReportController::class => self::MODULE_PERMISSION,
                InvoicedRevenueController::class => self::MODULE_PERMISSION,
            ],
            'Scheduling' => [
                SchedulingPermissions::JOBS_MY => self::MODULE_PERMISSION,
                SchedulingPermissions::JOBS_ALL => self::MODULE_PERMISSION,
            ],
            'Ticketing' => [
                TicketController::class => self::MODULE_PERMISSION,
            ],
            'Network' => [
                SiteController::class => self::MODULE_PERMISSION,
                DeviceController::class => self::MODULE_PERMISSION,
                DeviceInterfaceController::class => self::MODULE_PERMISSION,
                OutageController::class => self::MODULE_PERMISSION,
                UnknownDevicesController::class => self::MODULE_PERMISSION,
                NetworkMapController::class => self::MODULE_PERMISSION,
            ],
            'Reports' => [
                ReportDataUsageController::class => self::MODULE_PERMISSION,
            ],
            'System' => [
                OrganizationController::class => self::MODULE_PERMISSION,
                SettingController::class => self::MODULE_PERMISSION,
                WebhookEndpointController::class => self::MODULE_PERMISSION,
                PluginController::class => self::MODULE_PERMISSION,
            ],
            'System - Service plans & Products' => [
                TariffController::class => self::MODULE_PERMISSION,
                ProductController::class => self::MODULE_PERMISSION,
                SurchargeController::class => self::MODULE_PERMISSION,
            ],
            'System - Billing' => [
                SettingBillingController::class => self::MODULE_PERMISSION,
                SettingSuspendController::class => self::MODULE_PERMISSION,
                SettingFeesController::class => self::MODULE_PERMISSION,
                OrganizationBankAccountController::class => self::MODULE_PERMISSION,
                TaxController::class => self::MODULE_PERMISSION,
            ],
            'System - Customization' => [
                EmailTemplatesController::class => self::MODULE_PERMISSION,
                SuspensionTemplatesController::class => self::MODULE_PERMISSION,
                NotificationSettingsController::class => self::MODULE_PERMISSION,
                InvoiceTemplateController::class => self::MODULE_PERMISSION,
                AccountStatementTemplateController::class => self::MODULE_PERMISSION,
                QuoteTemplateController::class => self::MODULE_PERMISSION,
                PaymentReceiptTemplateController::class => self::MODULE_PERMISSION,
                AppearanceController::class => self::MODULE_PERMISSION,
                ClientZonePageController::class => self::MODULE_PERMISSION,
                ProformaInvoiceTemplateController::class => self::MODULE_PERMISSION,
            ],
            'System - Tools' => [
                BackupController::class => self::MODULE_PERMISSION,
                ClientImportController::class => self::MODULE_PERMISSION,
                PaymentImportController::class => self::MODULE_PERMISSION,
                CertificateController::class => self::MODULE_PERMISSION,
                WebrootController::class => self::MODULE_PERMISSION,
                DownloadController::class => self::MODULE_PERMISSION,
                FccReportsController::class => self::MODULE_PERMISSION,
                UpdatesController::class => self::MODULE_PERMISSION,
                MailingController::class => self::MODULE_PERMISSION,
            ],
            'System - Security' => [
                UserController::class => self::MODULE_PERMISSION,
                UserGroupController::class => self::MODULE_PERMISSION,
                AppKeyController::class => self::MODULE_PERMISSION,
            ],
            'System - Logs' => [
                DeviceLogController::class => self::MODULE_PERMISSION,
                EmailLogController::class => self::MODULE_PERMISSION,
                EntityLogController::class => self::MODULE_PERMISSION,
            ],
            'System - Other' => [
                VendorController::class => self::MODULE_PERMISSION,
                ServiceStopReasonController::class => self::MODULE_PERMISSION,
                CustomAttributeController::class => self::MODULE_PERMISSION,
                ClientTagController::class => self::MODULE_PERMISSION,
                ContactTypeController::class => self::MODULE_PERMISSION,
                SandboxTerminationController::class => self::MODULE_PERMISSION,
            ],
        ];
    }
}
