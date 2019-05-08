<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Form\Data\MailingComposeMessageData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MailingComposeMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'subject',
            TextType::class
        );

        $builder->add(
            'body',
            TextareaType::class,
            [
                'label' => 'Mail body',
                'attr' => [
                    'rows' => 8,
                ],
            ]
        );

        $builder->add(
            'send',
            SubmitType::class
        );

        $builder->add(
            'back',
            SubmitType::class
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => MailingComposeMessageData::class,
            ]
        );
    }
}
