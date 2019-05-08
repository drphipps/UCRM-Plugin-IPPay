<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Form\Event\FinancialItemTaxChoiceSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class FinancialItemSurchargeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'service',
            HiddenType::class,
            [
                'mapped' => false,
            ]
        );
        $builder->add(
            'surcharge',
            HiddenType::class,
            [
                'mapped' => false,
            ]
        );
        $builder->add('quantity', HiddenType::class);
        $builder->add('label', HiddenType::class);
        $builder->add('price', HiddenType::class);
        $builder->add('total', HiddenType::class);
        $builder->add('taxable');
        $builder->add('itemPosition', HiddenType::class);

        $builder->addEventSubscriber(new FinancialItemTaxChoiceSubscriber($options['multipleTaxes']));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('multipleTaxes');
        $resolver->setAllowedTypes('multipleTaxes', 'bool');
    }
}
