<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Form\Event\FinancialItemTaxChoiceSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class FinancialItemFeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'fee',
            HiddenType::class,
            [
                'mapped' => false,
            ]
        );
        $builder->add(
            'label',
            TextType::class,
            [
                'required' => true,
            ]
        );
        $builder->add('price', HiddenType::class);
        $builder->add('total', HiddenType::class);
        $builder->add('quantity', HiddenType::class);
        $builder->add(
            'taxable',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );
        $builder->add('itemPosition', HiddenType::class);

        $builder->addEventSubscriber(new FinancialItemTaxChoiceSubscriber($options['multipleTaxes']));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('multipleTaxes');
        $resolver->setAllowedTypes('multipleTaxes', 'bool');
    }
}
