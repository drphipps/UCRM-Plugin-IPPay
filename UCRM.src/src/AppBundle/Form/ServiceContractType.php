<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Service;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Service $service */
        $service = $options['data'];

        $builder->add(
            'contractId',
            TextType::class,
            [
                'label' => 'Contract ID',
                'required' => false,
            ]
        );

        $builder->add(
            'contractLengthType',
            ContractLengthTypeChoiceType::class,
            [
                'label' => 'Type',
            ]
        );

        $builder->add(
            'minimumContractLengthMonths',
            IntegerType::class,
            [
                'label' => 'Length',
                'required' => false,
            ]
        );

        if ($options['enableSetupFee']) {
            $builder->add(
                'setupFeePrice',
                FloatType::class,
                [
                    'label' => 'Setup fee',
                    'required' => false,
                    'mapped' => false,
                    'data' => $service->getSetupFee() ? $service->getSetupFee()->getPrice() : null,
                ]
            );
        }

        $builder->add(
            'earlyTerminationFeePrice',
            FloatType::class,
            [
                'label' => 'Early termination fee',
                'required' => false,
            ]
        );

        $builder->add(
            'contractEndDate',
            DateType::class,
            [
                'label' => 'End date',
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Service::class,
                'enableSetupFee' => false,
            ]
        );

        $resolver->setAllowedTypes('enableSetupFee', 'bool');
    }
}
