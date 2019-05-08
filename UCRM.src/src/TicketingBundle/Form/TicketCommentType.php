<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketCommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'body',
            TextareaType::class,
            [
                'attr' => [
                    'rows' => 10,
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'attachmentFiles',
            FileType::class,
            [
                'required' => false,
                'data_class' => null,
                'multiple' => true,
            ]
        );

        if ($options['show_private_toggle']) {
            $builder->add(
                'private',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'show_private_toggle' => false,
            ]
        );

        $resolver->setAllowedTypes('show_private_toggle', 'bool');
    }
}
