<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TicketingBundle\Form\Data\TicketNewData;

class ClientZoneTicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'subject',
            TextType::class,
            [
                'required' => true,
                'attr' => [
                    'placeholder' => 'Subject',
                ],
            ]
        );
        $builder->add(
            'message',
            TextareaType::class,
            [
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Write a question or attach a file',
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
                'label' => 'Attachments',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => TicketNewData::class,
            ]
        );
    }
}
