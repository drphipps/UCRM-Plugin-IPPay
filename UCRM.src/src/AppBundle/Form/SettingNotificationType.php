<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\NotificationsData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingNotificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'notificationCreatedDraftsByEmail',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_CREATED_DRAFTS_BY_EMAIL],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationCreatedDraftsInHeader',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_CREATED_DRAFTS_IN_HEADER],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationCreatedInvoicesByEmail',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_CREATED_INVOICES_BY_EMAIL],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationTicketClientCreatedByEmail',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_TICKET_CLIENT_CREATED_BY_EMAIL],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationTicketClientCreatedInHeader',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_TICKET_CLIENT_CREATED_IN_HEADER],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationTicketCommentClientCreatedByEmail',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_BY_EMAIL],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationTicketCommentClientCreatedInHeader',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_TICKET_COMMENT_CLIENT_CREATED_IN_HEADER],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationCreatedInvoicesInHeader',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_CREATED_INVOICES_IN_HEADER],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationInvoiceNew',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_INVOICE_NEW],
                'required' => false,
            ]
        );

        $builder->add(
            'sendInvoiceWithZeroBalance',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::SEND_INVOICE_WITH_ZERO_BALANCE],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationInvoiceNearDue',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_INVOICE_NEAR_DUE],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationInvoiceNearDueDays',
            IntegerType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_INVOICE_NEAR_DUE_DAYS],
                'attr' => [
                    'min' => 0,
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationInvoiceOverdue',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_INVOICE_OVERDUE],
                'required' => false,
            ]
        );

        $builder->add(
            'sendPaymentReceipts',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::SEND_PAYMENT_RECEIPTS],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationSubscriptionCancelled',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_SUBSCRIPTION_CANCELLED],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationSubscriptionAmountChanged',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_SUBSCRIPTION_AMOUNT_CHANGED],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationServiceSuspended',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_SERVICE_SUSPENDED],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationServiceSuspensionPostponed',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_SERVICE_SUSPENSION_POSTPONED],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationTicketUserCreatedByEmail',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_TICKET_USER_CREATED_BY_EMAIL],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationTicketCommentUserCreatedByEmail',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_TICKET_COMMENT_USER_CREATED_BY_EMAIL],
                'required' => false,
            ]
        );

        $builder->add(
            'notificationTicketUserChangedStatus',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::NOTIFICATION_TICKET_USER_CHANGED_STATUS],
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => NotificationsData::class,
            ]
        );
    }
}
