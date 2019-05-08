<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TicketingBundle\Entity\TicketImapInbox;

class TicketImapInboxType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'serverName',
            TextType::class
        );

        $builder->add(
            'serverPort',
            NumberType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'emailAddress',
            TextType::class
        );

        $builder->add(
            'username',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $passwordPlaceholder = (bool) $builder->getData()->getPassword()
            ? ['placeholder' => 'password must be provided again when changing settings']
            : [];
        $builder->add(
            'password',
            PasswordType::class,
            [
                'required' => false,
                'attr' => array_merge($passwordPlaceholder, ['autocomplete' => 'new-password']),
            ]
        );

        if ($options['include_is_default_option']) {
            $builder->add(
                'isDefault',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'Default',
                ]
            );
        }

        $builder->add(
            'verifySslCertificate',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Verify SSL certificate',
            ]
        );

        $builder->add(
            'ticketGroup',
            TicketGroupChoiceType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'importStartDate',
            DateType::class,
            [
                'html5' => false,
                'widget' => 'single_text',
            ]
        );

        $builder->add(
            'enabled',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Import enabled',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => TicketImapInbox::class,
                'include_is_default_option' => true,
            ]
        );

        $resolver->setAllowedTypes('include_is_default_option', 'bool');
    }
}
