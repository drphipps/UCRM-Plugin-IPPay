<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form\Data\Settings;

use AppBundle\Component\Annotation\Identifier;
use AppBundle\Entity\Option;
use Symfony\Component\Validator\Constraints as Assert;

final class NotificationsData implements SettingsDataInterface
{
    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_CREATED_DRAFTS_BY_EMAIL)
     */
    public $notificationCreatedDraftsByEmail;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_CREATED_DRAFTS_IN_HEADER)
     */
    public $notificationCreatedDraftsInHeader;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_CREATED_INVOICES_BY_EMAIL)
     */
    public $notificationCreatedInvoicesByEmail;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_CREATED_INVOICES_IN_HEADER)
     */
    public $notificationCreatedInvoicesInHeader;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_TICKET_CLIENT_CREATED_BY_EMAIL)
     */
    public $notificationTicketClientCreatedByEmail;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_TICKET_CLIENT_CREATED_IN_HEADER)
     */
    public $notificationTicketClientCreatedInHeader;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_BY_EMAIL)
     */
    public $notificationTicketCommentClientCreatedByEmail;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_IN_HEADER)
     */
    public $notificationTicketCommentClientCreatedInHeader;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_TICKET_COMMENT_USER_CREATED_BY_EMAIL)
     */
    public $notificationTicketCommentUserCreatedByEmail;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_TICKET_USER_CHANGED_STATUS)
     */
    public $notificationTicketUserChangedStatus;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_TICKET_USER_CREATED_BY_EMAIL)
     */
    public $notificationTicketUserCreatedByEmail;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_INVOICE_NEW)
     */
    public $notificationInvoiceNew;

    /**
     * @var bool
     *
     * @Identifier(Option::SEND_INVOICE_WITH_ZERO_BALANCE)
     */
    public $sendInvoiceWithZeroBalance;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_INVOICE_NEAR_DUE)
     */
    public $notificationInvoiceNearDue;

    /**
     * @var int
     *
     * @Identifier(Option::NOTIFICATION_INVOICE_NEAR_DUE_DAYS)
     *
     * @Assert\Range(min=0)
     */
    public $notificationInvoiceNearDueDays;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_INVOICE_OVERDUE)
     */
    public $notificationInvoiceOverdue;

    /**
     * @var bool
     *
     * @Identifier(Option::SEND_PAYMENT_RECEIPTS)
     */
    public $sendPaymentReceipts;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_SUBSCRIPTION_CANCELLED)
     */
    public $notificationSubscriptionCancelled;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_SUBSCRIPTION_AMOUNT_CHANGED)
     */
    public $notificationSubscriptionAmountChanged;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_SERVICE_SUSPENDED)
     */
    public $notificationServiceSuspended;

    /**
     * @var bool
     *
     * @Identifier(Option::NOTIFICATION_SERVICE_SUSPENSION_POSTPONED)
     */
    public $notificationServiceSuspensionPostponed;
}
