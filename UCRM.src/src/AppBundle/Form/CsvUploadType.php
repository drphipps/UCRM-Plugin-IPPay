<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Import\AbstractImport;
use AppBundle\Form\Data\CsvUploadData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CsvUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'file',
            FileType::class,
            [
                'attr' => [
                    'accept' => '.csv',
                ],
            ]
        );

        $builder->add(
            'delimiter',
            ChoiceType::class,
            [
                'choices' => AbstractImport::DELIMITERS_FORM,
            ]
        );

        $builder->add(
            'enclosure',
            ChoiceType::class,
            [
                'choices' => AbstractImport::ENCLOSURES_FORM,
            ]
        );

        $builder->add(
            'escape',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'maxlength' => 1,
                ],
            ]
        );

        $builder->add(
            'hasHeader',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'autoDetectStructure',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => CsvUploadData::class,
            ]
        );
    }
}
