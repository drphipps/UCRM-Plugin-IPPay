<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Service;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceDiscountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'discountType',
                ChoiceType::class,
                [
                    'choices' => array_flip(Service::DISCOUNT_TYPE_MODAL),
                ]
            )
            ->add(
                'discountValue',
                FloatType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'discountInvoiceLabel',
                TextType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'discountFrom',
                DateType::class,
                [
                    'required' => false,
                    'widget' => 'single_text',
                    'attr' => [
                        'style' => 'display:none',
                    ],
                ]
            )
            ->add(
                'discountTo',
                DateType::class,
                [
                    'required' => false,
                    'widget' => 'single_text',
                    'attr' => [
                        'style' => 'display:none',
                    ],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Service::class,
            ]
        );
    }
}
