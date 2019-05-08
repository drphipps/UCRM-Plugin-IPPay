<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Form\Type;

use SchedulingBundle\Entity\Job;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobSimpleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'title',
            TextType::class,
            [
                'required' => true,
                'attr' => [
                    'placeholder' => 'Title (required)',
                ],
            ]
        );

        $builder->add(
            'duration',
            JobDurationType::class,
            [
                'required' => false,
                'choices_with_units' => true,
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Description',
                ],
            ]
        );

        $builder->add(
            'address',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Address',
                ],
            ]
        );

        $builder->add(
            'enqueue',
            SubmitType::class
        );

        $builder->add(
            'schedule',
            SubmitType::class
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Job::class,
            ]
        );
    }
}
