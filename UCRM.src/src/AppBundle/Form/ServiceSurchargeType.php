<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\ServiceSurcharge;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSurchargeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'surcharge',
            SurchargeChoiceType::class,
            [
                'required' => true,
                'surcharge' => $builder->getData() ? $builder->getData()->getSurcharge() : null,
            ]
        );

        $builder->add(
            'invoiceLabel',
            TextType::class,
            [
                'required' => false,
                'label' => 'Invoice item label',
            ]
        );

        $builder->add(
            'price',
            FloatType::class,
            [
                'label' => 'Price per period',
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
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => ServiceSurcharge::class,
            ]
        );
    }
}
