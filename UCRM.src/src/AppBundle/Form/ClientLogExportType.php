<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientLogExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'fromDate',
            DateType::class,
            [
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'attr' => [
                    'placeholder' => 'From',
                ],
            ]
        );

        $builder->add(
            'toDate',
            DateType::class,
            [
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'attr' => [
                    'placeholder' => 'To',
                ],
            ]
        );

        $builder->add(
            'csvButton',
            SubmitType::class,
            [
                'label' => 'CSV',
            ]
        );

        $builder->add(
            'pdfButton',
            SubmitType::class,
            [
                'label' => 'PDF',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => null,
            ]
        );
    }
}
