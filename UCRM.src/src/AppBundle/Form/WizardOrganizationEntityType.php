<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Organization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotNull;

class WizardOrganizationEntityType extends AbstractType
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
            'registrationNumber',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'taxId',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'website',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'phone',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'email',
            EmailType::class,
            [
                'required' => true,
            ]
        );

        $builder->add(
            'street1',
            TextType::class,
            [
                'required' => true,
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
                'required' => true,
            ]
        );

        $builder->add(
            'country',
            CountryChoiceType::class,
            [
                'required' => true,
                'constraints' => [
                    new NotNull(),
                ],
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
                'required' => true,
            ]
        );

        $builder->add(
            'invoiceMaturityDays',
            IntegerType::class,
            [
                'required' => true,
            ]
        );

        $builder->add(
            'invoiceNumberPrefix',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'invoiceNumberLength',
            ChoiceType::class,
            [
                'required' => true,
                'choices' => array_combine(range(1, 20), range(1, 20)),
                'choice_translation_domain' => false,
            ]
        );

        $builder->add(
            'invoiceInitNumber',
            IntegerType::class,
            [
                'required' => true,
                'constraints' => [
                    new GreaterThanOrEqual(
                        [
                            'value' => 1,
                        ]
                    ),
                ],
                'attr' => [
                    'min' => 1,
                ],
            ]
        );

        $builder->add(
            'currency',
            CurrencyChoiceType::class,
            [
                'required' => true,
                'disabled' => $options['hasFinancialEntities'],
                'organization' => $options['organization'],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Organization::class,
                'hasFinancialEntities' => true,
            ]
        );
        $resolver->setRequired('organization');
        $resolver->setAllowedTypes('organization', Organization::class);
        $resolver->setRequired('hasFinancialEntities');
        $resolver->setAllowedTypes('hasFinancialEntities', 'bool');
    }
}
