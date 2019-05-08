<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 *
 * @see https://www.ubnt.com/
 */
declare(strict_types=1);

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

class StripeAchVerifyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'firstDeposit',
            IntegerType::class,
            [
                'label' => 'First deposit',
                'required' => true,
            ]
        );

        $builder->add(
            'secondDeposit',
            IntegerType::class,
            [
                'label' => 'Second deposit',
                'required' => true,
            ]
        );
    }
}
