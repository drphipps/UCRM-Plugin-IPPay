<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\BackupAdditionalData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingBackupAdditionalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'backupLifetimeCount',
            IntegerType::class,
            [
                'label' => Option::NAMES[Option::BACKUP_LIFETIME_COUNT],
                'attr' => [
                    'min' => 0,
                ],
            ]
        );

        $builder->add(
            'backupFilenamePrefix',
            TextType::class,
            [
                'label' => Option::NAMES[Option::BACKUP_FILENAME_PREFIX],
                'required' => false,
            ]
        );

        $builder->add(
            'backupRemoteDropbox',
            CheckboxType::class,
            [
                'label' => Option::NAMES[Option::BACKUP_REMOTE_DROPBOX],
                'required' => false,
            ]
        );

        $builder->add(
            'backupRemoteDropboxToken',
            TextType::class,
            [
                'label' => Option::NAMES[Option::BACKUP_REMOTE_DROPBOX_TOKEN],
                'required' => false,
            ]
        );

        $builder->add(
            'dropboxRequestSync',
            SubmitType::class,
            [
                'label' => 'Save and sync',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => BackupAdditionalData::class,
            ]
        );
    }
}
