<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

class EmailTemplatesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'system_notifications',
            CollectionType::class,
            [
                'entry_type' => NotificationTemplateType::class,
            ]
        );
        $builder->add(
            'billing',
            CollectionType::class,
            [
                'entry_type' => NotificationTemplateType::class,
            ]
        );
        $builder->add(
            'suspension',
            CollectionType::class,
            [
                'entry_type' => NotificationTemplateType::class,
            ]
        );
        $builder->add(
            'ticketing',
            CollectionType::class,
            [
                'entry_type' => NotificationTemplateType::class,
            ]
        );
    }
}
