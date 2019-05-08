<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Service;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceChangeAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'street1',
            TextType::class,
            [
                'required' => false,
            ]
        );
        $builder->add(
            'street2',
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
            'country',
            CountryChoiceType::class,
            [
                'required' => false,
            ]
        );
        $builder->add(
            'state',
            StateChoiceType::class,
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
        $builder->add(
            'addressGpsLat',
            NumberType::class,
            [
                'required' => false,
                'scale' => 12,
            ]
        );
        $builder->add(
            'addressGpsLon',
            NumberType::class,
            [
                'required' => false,
                'scale' => 12,
            ]
        );
        $zeroToNullTransformer = new CallbackTransformer(
            function (?float $value) {
                return $value === 0.0 ? null : $value;
            },
            function (?float $value) {
                return $value === 0.0 ? null : $value;
            }
        );
        $builder->get('addressGpsLat')->addModelTransformer($zeroToNullTransformer);
        $builder->get('addressGpsLon')->addModelTransformer($zeroToNullTransformer);
        $builder->add(
            'fccBlockId',
            TextType::class,
            [
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Service::class,
            ]
        );
    }
}
