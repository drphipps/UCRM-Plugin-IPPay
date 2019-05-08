<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Form\Data\CertificateUploadData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CertificateUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'certFile',
                FileType::class,
                [
                    'attr' => [
                        'accept' => '.crt',
                    ],
                ]
            );

        $builder->add(
            'caBundleFile',
            FileType::class,
            [
                'required' => false,
                'attr' => [
                    'accept' => '.ca-bundle',
                ],
            ]
        );

        $builder->add(
            'keyFile',
            FileType::class,
            [
                'attr' => [
                    'accept' => '.key',
                ],
            ]
        );

        $builder->add(
            'uploadButton',
            SubmitType::class
        );

        $builder->add(
            'disableButton',
            SubmitType::class
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => CertificateUploadData::class,
            ]
        );
    }
}
