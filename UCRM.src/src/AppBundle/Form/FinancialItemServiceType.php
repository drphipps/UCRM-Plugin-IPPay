<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */
declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\DataTransformer\DateTimeToStringTransformer;
use AppBundle\Form\Event\FinancialItemTaxChoiceSubscriber;
use AppBundle\Form\Type\DynamicChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class FinancialItemServiceType extends AbstractType
{
    /**
     * @var DateTimeToStringTransformer
     */
    protected $dateTimeToStringTransformer;

    public function __construct(DateTimeToStringTransformer $dateTimeToStringTransformer)
    {
        $this->dateTimeToStringTransformer = $dateTimeToStringTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'service',
            HiddenType::class,
            [
                'mapped' => false,
            ]
        );
        $builder->add('quantity', HiddenType::class);
        $builder->add(
            'label',
            HiddenType::class,
            [
                'required' => true,
            ]
        );
        $builder->add('price', HiddenType::class);
        $builder->add(
            'taxable',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );
        $builder->add(
            'invoicedFrom',
            DynamicChoiceType::class
        );
        $builder->add(
            'invoicedTo',
            DynamicChoiceType::class
        );
        $builder->add('total', HiddenType::class);
        $builder->add('discountValue', HiddenType::class);
        $builder->add('discountType', HiddenType::class);
        $builder->add('discountInvoiceLabel', HiddenType::class);
        $builder->add('discountFrom', HiddenType::class);
        $builder->add('discountTo', HiddenType::class);
        $builder->add('discountPrice', HiddenType::class);
        $builder->add('discountQuantity', HiddenType::class);
        $builder->add('discountTotal', HiddenType::class);
        $builder->add('itemPosition', HiddenType::class);

        $builder->get('invoicedFrom')->addModelTransformer($this->dateTimeToStringTransformer);
        $builder->get('invoicedTo')->addModelTransformer($this->dateTimeToStringTransformer);
        $builder->get('discountFrom')->addModelTransformer($this->dateTimeToStringTransformer);
        $builder->get('discountTo')->addModelTransformer($this->dateTimeToStringTransformer);

        $builder->addEventSubscriber(new FinancialItemTaxChoiceSubscriber($options['multipleTaxes']));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('multipleTaxes');
        $resolver->setAllowedTypes('multipleTaxes', 'bool');
    }
}
