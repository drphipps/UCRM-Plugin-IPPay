<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

class CsvImportPaymentsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            '_jsonSerialized',
            HiddenType::class
        );

        $builder->add(
            'payments',
            CollectionType::class,
            [
                'entry_type' => CsvImportPaymentRowType::class,
                'allow_add' => true,
                'allow_delete' => false,
                'by_reference' => false,
            ]
        );

        $builder->add(
            'importRow',
            CollectionType::class,
            [
                'entry_type' => CheckboxType::class,
                'entry_options' => [
                    'attr' => ['class' => 'enable-import-checkbox'],
                    'required' => false,
                ],
            ]
        );
    }
}
