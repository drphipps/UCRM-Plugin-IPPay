<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Form\Data\MailingPreviewData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MailingPreviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'send',
            CollectionType::class,
            [
                'entry_type' => CheckboxType::class,
                'entry_options' => [
                    'required' => false,
                ],
                'allow_add' => true,
                'by_reference' => false,
            ]
        );

        $builder->add(
            'next',
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
                'data_class' => MailingPreviewData::class,
            ]
        );
    }
}
