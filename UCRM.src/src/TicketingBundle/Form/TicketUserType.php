<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Form;

use AppBundle\Form\ClientChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TicketingBundle\Form\Data\TicketNewUserData;

class TicketUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'subject',
            TextType::class
        );
        $builder->add(
            'message',
            TextareaType::class,
            [
                'attr' => [
                    'rows' => 10,
                ],
                'required' => false,
            ]
        );

        if ($options['show_client_select']) {
            $builder->add(
                'client',
                ClientChoiceType::class,
                [
                    'include_leads' => true,
                ]
            );
        }

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
        $builder->add(
            'private',
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
                'data_class' => TicketNewUserData::class,
                'show_client_select' => true,
            ]
        );

        $resolver->setAllowedTypes('show_client_select', 'bool');
    }
}
