<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Form\Data\ServiceEndData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceEndType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['earlyTerminationFeeCheckbox']) {
            $builder->add(
                'allowEarlyTerminationFee',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ServiceEndData::class,
            ]
        );

        $resolver->setRequired('earlyTerminationFeeCheckbox');
        $resolver->setAllowedTypes('earlyTerminationFeeCheckbox', 'bool');
    }
}
