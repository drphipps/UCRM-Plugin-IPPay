<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
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
            'invoiceLabel',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'price',
            FloatType::class,
            [
                'required' => true,
            ]
        );

        $builder->add(
            'unit',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'taxable',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'tax',
            TaxChoiceType::class,
            [
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Product::class,
            ]
        );
    }
}
