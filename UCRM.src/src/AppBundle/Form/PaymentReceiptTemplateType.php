<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\PaymentReceiptTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentReceiptTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'required' => true,
            ]
        );

        $builder->add(
            'twig',
            TextareaType::class,
            [
                'required' => false, // needs to be false because of CodeMirror
                'mapped' => false,
            ]
        );

        $builder->add(
            'css',
            TextareaType::class,
            [
                'required' => false, // needs to be false because of CodeMirror
                'mapped' => false,
                'label' => 'CSS',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => PaymentReceiptTemplate::class,
            ]
        );
    }
}
