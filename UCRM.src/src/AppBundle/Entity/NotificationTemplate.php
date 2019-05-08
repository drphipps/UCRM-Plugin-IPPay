<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class NotificationTemplate implements LoggableInterface, ParentLoggableInterface
{
    // notification types
    public const ADMIN_DRAFT_CREATED = 1;
    public const CLIENT_INVITATION = 2;
    public const CLIENT_NEW_INVOICE = 3;
    public const CLIENT_OVERDUE_INVOICE = 4;
    public const CLIENT_SUSPEND_SERVICE = 5;
    public const CLIENT_FORGOTTEN_PASSWORD = 6;
    public const CLIENT_POSTPONE_SUSPEND = 7;
    public const SUSPEND_ANONYMOUS = 8;
    public const SUSPEND_RECOGNIZED = 9;
    public const CLIENT_PAYMENT_RECEIPT = 11;
    public const SUSPEND_TERMINATED = 14;
    public const SUSPEND_PREPARED = 15;
    public const CLIENT_NEAR_DUE_INVOICE = 16;
    public const ADMIN_INVOICE_CREATED = 17;
    public const SUBSCRIPTION_CANCELLED_IPPAY = 18;
    public const TICKET_CREATED_BY_USER = 19;
    public const TICKET_COMMENTED_BY_USER_WITHOUT_IMAP = 20;
    public const TICKET_CHANGED_STATUS = 21;
    public const TICKET_COMMENTED_BY_USER_TO_EMAIL = 22;
    public const TICKET_COMMENTED_BY_USER_WITH_IMAP = 24;
    public const SUSPEND_CUSTOM_REASON = 25;
    public const TICKET_AUTOMATIC_REPLY = 26;
    public const SUBSCRIPTION_CANCELLED = 27;
    public const CLIENT_NEW_QUOTE = 28;
    public const SUBSCRIPTION_AMOUNT_CHANGED = 29;
    public const CLIENT_NEW_PROFORMA_INVOICE = 30;

    public const NOTIFICATION_TYPES = [
        self::ADMIN_DRAFT_CREATED => 'Draft created',
        self::ADMIN_INVOICE_CREATED => 'Invoice created',
        self::CLIENT_FORGOTTEN_PASSWORD => 'Forgotten password',
        self::CLIENT_INVITATION => 'Invitation email',
        self::CLIENT_NEAR_DUE_INVOICE => 'Invoice near due date',
        self::CLIENT_NEW_INVOICE => 'New invoice',
        self::CLIENT_NEW_PROFORMA_INVOICE => 'New proforma invoice',
        self::CLIENT_NEW_QUOTE => 'New quote',
        self::CLIENT_OVERDUE_INVOICE => 'Invoice overdue',
        self::CLIENT_PAYMENT_RECEIPT => 'Payment receipt',
        self::CLIENT_POSTPONE_SUSPEND => 'Postpone suspend',
        self::CLIENT_SUSPEND_SERVICE => 'Service suspended',
        self::SUBSCRIPTION_AMOUNT_CHANGED => 'Subscription amount changed',
        self::SUBSCRIPTION_CANCELLED => 'Subscription cancelled',
        self::SUSPEND_ANONYMOUS => 'Suspend anonymous',
        self::SUSPEND_PREPARED => 'Suspend prepared',
        self::SUSPEND_RECOGNIZED => 'Suspend recognized',
        self::SUSPEND_TERMINATED => 'Suspend terminated',
        self::SUSPEND_CUSTOM_REASON => 'Suspension for manually stopped services',
        self::TICKET_CHANGED_STATUS => 'Ticket changed status',
        self::TICKET_COMMENTED_BY_USER_WITHOUT_IMAP => 'Ticket commented by admin',
        self::TICKET_CREATED_BY_USER => 'Ticket created by admin',
        self::TICKET_COMMENTED_BY_USER_TO_EMAIL => 'Ticket commented by admin (unknown client)',
        self::TICKET_COMMENTED_BY_USER_WITH_IMAP => 'Ticket commented by admin (with IMAP enabled)',
        self::TICKET_AUTOMATIC_REPLY => 'Automatic reply to new ticket',
    ];

    public const POSSIBLE_NOTIFICATION_TYPES = [
        self::ADMIN_DRAFT_CREATED,
        self::ADMIN_INVOICE_CREATED,
        self::CLIENT_FORGOTTEN_PASSWORD,
        self::CLIENT_INVITATION,
        self::CLIENT_NEAR_DUE_INVOICE,
        self::CLIENT_NEW_INVOICE,
        self::CLIENT_NEW_PROFORMA_INVOICE,
        self::CLIENT_NEW_QUOTE,
        self::CLIENT_OVERDUE_INVOICE,
        self::CLIENT_PAYMENT_RECEIPT,
        self::CLIENT_POSTPONE_SUSPEND,
        self::CLIENT_SUSPEND_SERVICE,
        self::SUBSCRIPTION_AMOUNT_CHANGED,
        self::SUBSCRIPTION_CANCELLED,
        self::SUSPEND_ANONYMOUS,
        self::SUSPEND_PREPARED,
        self::SUSPEND_RECOGNIZED,
        self::SUSPEND_TERMINATED,
        self::SUSPEND_CUSTOM_REASON,
        self::TICKET_CHANGED_STATUS,
        self::TICKET_COMMENTED_BY_USER_WITHOUT_IMAP,
        self::TICKET_CREATED_BY_USER,
        self::TICKET_COMMENTED_BY_USER_TO_EMAIL,
        self::TICKET_COMMENTED_BY_USER_WITH_IMAP,
        self::TICKET_AUTOMATIC_REPLY,
    ];

    public const NOTIFICATION_TOOLTIPS = [
        self::ADMIN_DRAFT_CREATED => 'Email sent to administrator with notification about newly created invoice drafts.',
        self::ADMIN_INVOICE_CREATED => 'Email sent to administrator with notification about newly created, automatically approved invoices.',
        self::CLIENT_FORGOTTEN_PASSWORD => 'Email sent to clients who want to reset their password.',
        self::CLIENT_INVITATION => 'Email sent to new clients with instructions on how to access the UCRM client zone.',
        self::CLIENT_NEAR_DUE_INVOICE => 'Email reminder sent to clients whose unpaid invoice\'s due date is coming up.',
        self::CLIENT_NEW_INVOICE => 'Email sent to client with a newly created invoice.',
        self::CLIENT_NEW_PROFORMA_INVOICE => 'Email sent to client with a newly created proforma invoice.',
        self::CLIENT_NEW_QUOTE => 'Email sent to client with a newly created quote.',
        self::CLIENT_OVERDUE_INVOICE => 'Email sent to clients whose invoice became overdue.',
        self::CLIENT_PAYMENT_RECEIPT => 'Email sent to client with a newly created payment receipt.',
        self::CLIENT_POSTPONE_SUSPEND => 'Email sent to client whose suspension has been postponed manually.',
        self::CLIENT_SUSPEND_SERVICE => 'Email notification sent when client\'s service was suspended.',
        self::SUBSCRIPTION_AMOUNT_CHANGED => 'Email sent to client whose payment subscription amount has been changed.',
        self::SUBSCRIPTION_CANCELLED => 'Email sent to client whose payment subscription has been canceled.',
        self::SUSPEND_ANONYMOUS => 'Text shown on the suspension "walled garden" page for all suspended clients whose IP was not recognized (UCRM wasn\'t able to match client\'s IP with any service device IP).',
        self::SUSPEND_PREPARED => 'Text shown on the suspension "walled garden" page for clients whose service has not been activated yet.',
        self::SUSPEND_RECOGNIZED => 'Text shown on the suspension "walled garden" page for all suspended clients whose IP was recognized (The IP matches with some service device IP).',
        self::SUSPEND_TERMINATED => 'Text shown on the suspension "walled garden" page for clients whose service has been terminated.',
        self::SUSPEND_CUSTOM_REASON => 'Text shown on the suspension "walled garden" page for clients whose service has been stopped with custom reason.',
        self::TICKET_CHANGED_STATUS => 'Email sent to client when ticket changes status.',
        self::TICKET_COMMENTED_BY_USER_WITHOUT_IMAP => 'Email sent to client when admin comments ticket.',
        self::TICKET_CREATED_BY_USER => 'Email sent to client when admin creates new ticket.',
        self::TICKET_COMMENTED_BY_USER_TO_EMAIL => 'Email sent to unknown client when admin comments ticket. Applies when ticket was created by IMAP integration and sender email address did not match any UCRM client.',
        self::TICKET_COMMENTED_BY_USER_WITH_IMAP => 'Email sent to client when admin comments ticket. Applies when ticket was created by IMAP integration.',
        self::TICKET_AUTOMATIC_REPLY => 'Email sent to client when new ticket is created by IMAP integration.',
    ];

    public const EMAIL_TYPES = [
        self::ADMIN_DRAFT_CREATED,
        self::ADMIN_INVOICE_CREATED,
        self::CLIENT_FORGOTTEN_PASSWORD,
        self::CLIENT_INVITATION,
        self::CLIENT_NEAR_DUE_INVOICE,
        self::CLIENT_NEW_INVOICE,
        self::CLIENT_NEW_PROFORMA_INVOICE,
        self::CLIENT_NEW_QUOTE,
        self::CLIENT_OVERDUE_INVOICE,
        self::CLIENT_PAYMENT_RECEIPT,
        self::CLIENT_POSTPONE_SUSPEND,
        self::CLIENT_SUSPEND_SERVICE,
        self::SUBSCRIPTION_AMOUNT_CHANGED,
        self::SUBSCRIPTION_CANCELLED,
        self::TICKET_CHANGED_STATUS,
        self::TICKET_COMMENTED_BY_USER_WITHOUT_IMAP,
        self::TICKET_CREATED_BY_USER,
        self::TICKET_COMMENTED_BY_USER_TO_EMAIL,
        self::TICKET_COMMENTED_BY_USER_WITH_IMAP,
        self::TICKET_AUTOMATIC_REPLY,
    ];

    public const CATEGORY_SYSTEM_NOTIFICATIONS = 'system_notifications';
    public const CATEGORY_BILLING = 'billing';
    public const CATEGORY_SUSPENSION = 'suspension';
    public const CATEGORY_TICKETING = 'ticketing';

    public const EMAIL_TYPES_CATEGORIES = [
        self::CATEGORY_SYSTEM_NOTIFICATIONS => [
            self::ADMIN_DRAFT_CREATED,
            self::ADMIN_INVOICE_CREATED,
            self::CLIENT_FORGOTTEN_PASSWORD,
            self::CLIENT_INVITATION,
        ],
        self::CATEGORY_BILLING => [
            self::CLIENT_NEW_INVOICE,
            self::CLIENT_NEW_PROFORMA_INVOICE,
            self::CLIENT_NEAR_DUE_INVOICE,
            self::CLIENT_OVERDUE_INVOICE,
            self::CLIENT_PAYMENT_RECEIPT,
            self::SUBSCRIPTION_CANCELLED,
            self::SUBSCRIPTION_AMOUNT_CHANGED,
            self::CLIENT_NEW_QUOTE,
        ],
        self::CATEGORY_SUSPENSION => [
            self::CLIENT_SUSPEND_SERVICE,
            self::CLIENT_POSTPONE_SUSPEND,
        ],
        self::CATEGORY_TICKETING => [
            self::TICKET_CHANGED_STATUS,
            self::TICKET_COMMENTED_BY_USER_WITH_IMAP,
            self::TICKET_COMMENTED_BY_USER_WITHOUT_IMAP,
            self::TICKET_COMMENTED_BY_USER_TO_EMAIL,
            self::TICKET_CREATED_BY_USER,
            self::TICKET_AUTOMATIC_REPLY,
        ],
    ];

    public const SUSPENSION_TYPES = [
        self::SUSPEND_ANONYMOUS,
        self::SUSPEND_PREPARED,
        self::SUSPEND_RECOGNIZED,
        self::SUSPEND_TERMINATED,
        self::SUSPEND_CUSTOM_REASON,
    ];

    public const CLIENT_TYPES = [
        self::CLIENT_FORGOTTEN_PASSWORD,
        self::CLIENT_INVITATION,
        self::CLIENT_NEAR_DUE_INVOICE,
        self::CLIENT_NEW_INVOICE,
        self::CLIENT_NEW_PROFORMA_INVOICE,
        self::CLIENT_NEW_QUOTE,
        self::CLIENT_OVERDUE_INVOICE,
        self::CLIENT_PAYMENT_RECEIPT,
        self::CLIENT_POSTPONE_SUSPEND,
        self::CLIENT_SUSPEND_SERVICE,
        self::SUBSCRIPTION_AMOUNT_CHANGED,
        self::SUBSCRIPTION_CANCELLED,
        self::SUSPEND_PREPARED,
        self::SUSPEND_RECOGNIZED,
        self::SUSPEND_TERMINATED,
        self::SUSPEND_CUSTOM_REASON,
        self::TICKET_CHANGED_STATUS,
        self::TICKET_COMMENTED_BY_USER_WITHOUT_IMAP,
        self::TICKET_CREATED_BY_USER,
        self::TICKET_COMMENTED_BY_USER_WITH_IMAP,
    ];

    public const SERVICE_TYPES = [
        self::CLIENT_POSTPONE_SUSPEND,
        self::CLIENT_SUSPEND_SERVICE,
        self::SUSPEND_PREPARED,
        self::SUSPEND_RECOGNIZED,
        self::SUSPEND_CUSTOM_REASON,
    ];

    public const INVOICE_TYPES = [
        self::CLIENT_NEAR_DUE_INVOICE,
        self::CLIENT_NEW_INVOICE,
        self::CLIENT_NEW_PROFORMA_INVOICE,
        self::CLIENT_OVERDUE_INVOICE,
        self::CLIENT_SUSPEND_SERVICE,
    ];

    public const SERVICE_SUSPEND_TYPES = [
        self::SUSPEND_RECOGNIZED,
        self::SUSPEND_CUSTOM_REASON,
    ];

    public const DRAFT_TYPES = [
        self::ADMIN_DRAFT_CREATED,
        self::ADMIN_INVOICE_CREATED,
    ];

    public const TICKET_TYPES = [
        self::TICKET_CHANGED_STATUS,
        self::TICKET_COMMENTED_BY_USER_WITHOUT_IMAP,
        self::TICKET_CREATED_BY_USER,
    ];

    public const TICKET_BY_EMAIL_TYPES = [
        self::TICKET_COMMENTED_BY_USER_TO_EMAIL,
        self::TICKET_CHANGED_STATUS,
        self::TICKET_COMMENTED_BY_USER_WITHOUT_IMAP,
        self::TICKET_CREATED_BY_USER,
        self::TICKET_COMMENTED_BY_USER_WITH_IMAP,
        self::TICKET_AUTOMATIC_REPLY,
    ];

    public const TICKET_MESSAGE_TYPES = [
        self::TICKET_COMMENTED_BY_USER_WITHOUT_IMAP,
        self::TICKET_CREATED_BY_USER,
        self::TICKET_COMMENTED_BY_USER_TO_EMAIL,
        self::TICKET_COMMENTED_BY_USER_WITH_IMAP,
        self::TICKET_AUTOMATIC_REPLY,
    ];

    // Used to sort templates order in suspension templates settings
    public const SUSPENSION_TYPES_SORT = [
        self::SUSPEND_ANONYMOUS,
        self::SUSPEND_RECOGNIZED,
        self::SUSPEND_PREPARED,
        self::SUSPEND_TERMINATED,
        self::SUSPEND_CUSTOM_REASON,
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="template_id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="subject", type="string", length=100)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max = 100)
     */
    protected $subject;

    /**
     * @var string|null
     *
     * @ORM\Column(name="body", type="text")
     *
     * @Assert\NotBlank()
     */
    protected $body;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="integer")
     * @Assert\Choice(choices=NotificationTemplate::POSSIBLE_NOTIFICATION_TYPES, strict=true)
     */
    protected $type;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): void
    {
        $this->subject = $subject;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = $body;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    public function setType(int $type): NotificationTemplate
    {
        $this->type = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogInsertMessage()
    {
        $message['logMsg'] = [
            'message' => 'Notification template %s added',
            'replacements' => self::NOTIFICATION_TYPES[$this->getType()],
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogUpdateMessage()
    {
        $message['logMsg'] = [
            'id' => $this->getId(),
            'message' => self::NOTIFICATION_TYPES[$this->getType()],
            'entity' => self::class,
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDeleteMessage()
    {
        $message['logMsg'] = [
            'message' => 'Notification template %s deleted',
            'replacements' => self::NOTIFICATION_TYPES[$this->getType()],
        ];

        return $message;
    }

    /**
     * {@inheritdoc}
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
}
