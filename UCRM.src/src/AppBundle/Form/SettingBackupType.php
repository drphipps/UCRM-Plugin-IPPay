<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\BackupData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingBackupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'backupIncludeInvoiceTemplates',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::BACKUP_INCLUDE_INVOICE_TEMPLATES],
                'required' => false,
            ]
        );

        $builder->add(
            'backupIncludeQuoteTemplates',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::BACKUP_INCLUDE_QUOTE_TEMPLATES],
                'required' => false,
            ]
        );

        $builder->add(
            'backupIncludeAccountStatementTemplates',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::BACKUP_INCLUDE_ACCOUNT_STATEMENT_TEMPLATES],
                'required' => false,
            ]
        );

        $builder->add(
            'backupIncludePaymentReceiptTemplates',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::BACKUP_INCLUDE_PAYMENT_RECEIPT_TEMPLATES],
                'required' => false,
            ]
        );

        $builder->add(
            'backupIncludeSslCertificates',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::BACKUP_INCLUDE_SSL_CERTIFICATES],
                'required' => false,
            ]
        );

        $builder->add(
            'backupIncludeDownloads',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::BACKUP_INCLUDE_DOWNLOADS],
                'required' => false,
            ]
        );

        $builder->add(
            'backupIncludeMedia',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::BACKUP_INCLUDE_MEDIA],
                'required' => false,
            ]
        );

        $builder->add(
            'backupIncludeWebroot',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::BACKUP_INCLUDE_WEBROOT],
                'required' => false,
            ]
        );

        $builder->add(
            'backupIncludeDocuments',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::BACKUP_INCLUDE_DOCUMENTS],
                'required' => false,
            ]
        );

        $builder->add(
            'backupIncludePlugins',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::BACKUP_INCLUDE_PLUGINS],
                'required' => false,
            ]
        );

        $builder->add(
            'backupIncludeTicketAttachments',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::BACKUP_INCLUDE_TICKET_ATTACHMENTS],
                'required' => false,
            ]
        );

        $builder->add(
            'backupIncludeJobAttachments',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::BACKUP_INCLUDE_JOB_ATTACHMENTS],
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => BackupData::class,
            ]
        );
    }
}
