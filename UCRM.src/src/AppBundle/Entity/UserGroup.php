<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use AppBundle\Controller\AccountStatementTemplateController;
use AppBundle\Controller\AppearanceController;
use AppBundle\Controller\AppKeyController;
use AppBundle\Controller\BackupController;
use AppBundle\Controller\CertificateController;
use AppBundle\Controller\ClientController;
use AppBundle\Controller\ClientImportController;
use AppBundle\Controller\ClientTagController;
use AppBundle\Controller\ClientZonePageController;
use AppBundle\Controller\ContactTypeController;
use AppBundle\Controller\CustomAttributeController;
use AppBundle\Controller\DeviceController;
use AppBundle\Controller\DeviceInterfaceController;
use AppBundle\Controller\DeviceLogController;
use AppBundle\Controller\DocumentController;
use AppBundle\Controller\DownloadController;
use AppBundle\Controller\EmailLogController;
use AppBundle\Controller\EmailTemplatesController;
use AppBundle\Controller\EntityLogController;
use AppBundle\Controller\FccReportsController;
use AppBundle\Controller\InvoiceController;
use AppBundle\Controller\InvoicedRevenueController;
use AppBundle\Controller\InvoiceTemplateController;
use AppBundle\Controller\MailingController;
use AppBundle\Controller\NetworkMapController;
use AppBundle\Controller\NotificationSettingsController;
use AppBundle\Controller\OrganizationBankAccountController;
use AppBundle\Controller\OrganizationController;
use AppBundle\Controller\OutageController;
use AppBundle\Controller\PaymentController;
use AppBundle\Controller\PaymentImportController;
use AppBundle\Controller\PaymentReceiptTemplateController;
use AppBundle\Controller\PluginController;
use AppBundle\Controller\ProductController;
use AppBundle\Controller\ProformaInvoiceTemplateController;
use AppBundle\Controller\QuoteController;
use AppBundle\Controller\QuoteTemplateController;
use AppBundle\Controller\RefundController;
use AppBundle\Controller\ReportDataUsageController;
use AppBundle\Controller\SandboxTerminationController;
use AppBundle\Controller\ServiceController;
use AppBundle\Controller\ServiceStopReasonController;
use AppBundle\Controller\SettingBillingController;
use AppBundle\Controller\SettingController;
use AppBundle\Controller\SettingFeesController;
use AppBundle\Controller\SettingSuspendController;
use AppBundle\Controller\SiteController;
use AppBundle\Controller\SurchargeController;
use AppBundle\Controller\SuspensionTemplatesController;
use AppBundle\Controller\TariffController;
use AppBundle\Controller\TaxController;
use AppBundle\Controller\TaxReportController;
use AppBundle\Controller\UnknownDevicesController;
use AppBundle\Controller\UpdatesController;
use AppBundle\Controller\UserController;
use AppBundle\Controller\UserGroupController;
use AppBundle\Controller\VendorController;
use AppBundle\Controller\WebhookEndpointController;
use AppBundle\Controller\WebrootController;
use AppBundle\Security\Permission;
use AppBundle\Security\PermissionNames;
use AppBundle\Security\SpecialPermission;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use TicketingBundle\Controller\TicketController;

/**
 * NEW CONTROLLER NEEDS TO BE ADDED TO 3 PLACES!
 *
 * @see https://ubiquiti.atlassian.net/wiki/display/ABR/Technical+documentation#Technicaldocumentation-Security
 *
 * @ORM\Entity
 */
class UserGroup implements LoggableInterface, ParentLoggableInterface
{
    public const USER_GROUP_MAX_SYSTEM_ID = 1000;

    /**
     * All controllers which require validation through PermissionSubscriber should be here.
     *
     * @see PermissionNames::PERMISSION_HUMAN_NAMES
     * @see PermissionNames::PERMISSION_SYSTEM_NAMES
     * @see UserGroupController::getModulePermissionGroups()
     */
    public const PERMISSION_MODULES = [
        AccountStatementTemplateController::class,
        AppearanceController::class,
        AppKeyController::class,
        BackupController::class,
        CertificateController::class,
        ClientController::class,
        ClientTagController::class,
        ClientZonePageController::class,
        ContactTypeController::class,
        CustomAttributeController::class,
        DeviceController::class,
        DeviceInterfaceController::class,
        DeviceLogController::class,
        DocumentController::class,
        DownloadController::class,
        EmailLogController::class,
        EmailTemplatesController::class,
        EntityLogController::class,
        FccReportsController::class,
        ClientImportController::class,
        PaymentImportController::class,
        InvoiceController::class,
        InvoicedRevenueController::class,
        InvoiceTemplateController::class,
        MailingController::class,
        NetworkMapController::class,
        NotificationSettingsController::class,
        OrganizationBankAccountController::class,
        OrganizationController::class,
        OutageController::class,
        PaymentController::class,
        PluginController::class,
        ProductController::class,
        QuoteController::class,
        QuoteTemplateController::class,
        PaymentReceiptTemplateController::class,
        RefundController::class,
        ReportDataUsageController::class,
        SandboxTerminationController::class,
        ServiceController::class,
        ServiceStopReasonController::class,
        SettingBillingController::class,
        SettingController::class,
        SettingFeesController::class,
        SettingSuspendController::class,
        SiteController::class,
        SurchargeController::class,
        SuspensionTemplatesController::class,
        TariffController::class,
        TaxController::class,
        TaxReportController::class,
        TicketController::class,
        UnknownDevicesController::class,
        UpdatesController::class,
        UserController::class,
        UserGroupController::class,
        VendorController::class,
        WebhookEndpointController::class,
        WebrootController::class,
        ProformaInvoiceTemplateController::class,
    ];

    /**
     * All special permissions.
     *
     * @see SpecialPermission
     * @see PermissionNames::SPECIAL_PERMISSIONS_HUMAN_NAMES
     * @see PermissionNames::SPECIAL_PERMISSIONS_SYSTEM_NAMES
     */
    public const SPECIAL_PERMISSIONS = [
        SpecialPermission::CLIENT_ACCOUNT_STANDING,
        SpecialPermission::CLIENT_EXPORT,
        SpecialPermission::CLIENT_IMPERSONATION,
        SpecialPermission::CLIENT_LOG_EDIT,
        SpecialPermission::JOB_COMMENT_EDIT,
        SpecialPermission::SHOW_DEVICE_PASSWORDS,
        SpecialPermission::FINANCIAL_OVERVIEW,
        SpecialPermission::PAYMENT_CREATE,
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="group_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\Length(max = 255)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var Collection|User[]
     *
     * @ORM\OneToMany(targetEntity="User", mappedBy="group")
     */
    protected $users;

    /**
     * @var Collection|UserGroupPermission[]
     *
     * @ORM\OneToMany(targetEntity="UserGroupPermission", mappedBy="group", cascade={"persist", "remove"})
     */
    protected $permissions;

    /**
     * @var Collection|UserGroupSpecialPermission[]
     *
     * @ORM\OneToMany(targetEntity="UserGroupSpecialPermission", mappedBy="group", cascade={"persist", "remove"})
     */
    protected $specialPermissions;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->permissions = new ArrayCollection();
        $this->specialPermissions = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return UserGroup
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return UserGroup
     */
    public function addUser(User $user)
    {
        $this->users[] = $user;

        return $this;
    }

    public function removeUser(User $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return UserGroup
     */
    public function addPermission(UserGroupPermission $permission)
    {
        $permission->setGroup($this);
        $this->permissions[] = $permission;

        return $this;
    }

    public function removePermission(UserGroupPermission $permission)
    {
        $this->permissions->removeElement($permission);
    }

    /**
     * @return Collection|UserGroupPermission[]
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    public function getPermission(string $moduleName): UserGroupPermission
    {
        return $this->getPermissions()->filter(
            function (UserGroupPermission $permission) use ($moduleName) {
                return $permission->getModuleName() === $moduleName;
            }
        )->first();
    }

    public function getSpecialPermission(string $moduleName): UserGroupSpecialPermission
    {
        return $this->getSpecialPermissions()->filter(
            function (UserGroupSpecialPermission $permission) use ($moduleName) {
                return $permission->getModuleName() === $moduleName;
            }
        )->first();
    }

    /**
     * Inits all modules with default permission to the appGroup
     * The user_group can already posses some of the available modules set in self::PERMISSION_MODULES.
     *
     * @param string $defaultPermission
     */
    public function initPermissions($defaultPermission = Permission::DENIED)
    {
        $used = [];
        foreach ($this->getPermissions() as $permission) {
            $used[] = $permission->getModuleName();
        }

        foreach (self::PERMISSION_MODULES as $moduleName) {
            if (! in_array($moduleName, $used)) {
                $perm = new UserGroupPermission();
                $perm->setModuleName($moduleName);
                $perm->setPermission($defaultPermission);
                $this->addPermission($perm);
            }
        }
    }

    /**
     * Returns name for EntityType in forms.
     *
     * @return string
     */
    public function getGroup()
    {
        return $this->name;
    }

    /**
     * Get delete message for log.
     *
     * @return array
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'User group %s deleted',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * Get insert message for log.
     *
     * @return array
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'User group %s added',
            'replacements' => $this->getName(),
        ];

        return $message;
    }

    /**
     * Get unloggable columns for log.
     *
     * @return array
     */
    public function getLogIgnoredColumns()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogClient()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogSite()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogParentEntity()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => $this->getName(),
            'entity' => self::class,
        ];

        return $message;
    }

    /**
     * @return UserGroup
     */
    public function addSpecialPermission(UserGroupSpecialPermission $specialPermission)
    {
        $specialPermission->setGroup($this);
        $this->specialPermissions[] = $specialPermission;

        return $this;
    }

    public function removeSpecialPermission(UserGroupSpecialPermission $specialPermission)
    {
        $this->specialPermissions->removeElement($specialPermission);
    }

    /**
     * @return Collection|UserGroupSpecialPermission[]
     */
    public function getSpecialPermissions()
    {
        return $this->specialPermissions;
    }
}
