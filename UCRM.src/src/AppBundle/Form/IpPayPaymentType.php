<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Form\Data\IpPayPaymentData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IpPayPaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'cardNumber',
            TextType::class,
            [
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ]
        );

        $builder->add(
            'cardExpiration',
            TextType::class,
            [
                'attr' => [
                    'autocomplete' => 'off',
                    'placeholder' => 'MM/YY',
                ],
            ]
        );

        $builder->add(
            'CVV2',
            TextType::class,
            [
                'label' => 'CVV/CVC',
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ]
        );

        $builder->add(
            'address',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'city',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'state',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'country',
            CountryChoiceType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'zipCode',
            TextType::class,
            [
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => IpPayPaymentData::class,
            ]
        );
    }
}
