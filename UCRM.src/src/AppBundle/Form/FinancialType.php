<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Client;
use AppBundle\Entity\Financial\FinancialInterface;
use AppBundle\Entity\OrganizationBankAccount;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

abstract class FinancialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var FinancialInterface $financial */
        $financial = $options['data'];

        $builder->add(
            'createdDate',
            DateTimeType::class,
            [
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
            ]
        );

        $builder->add(
            'comment',
            TextareaType::class,
            [
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ]
        );

        $builder->add(
            'notes',
            TextareaType::class,
            [
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ]
        );

        $builder->add(
            'discountValue',
            FloatType::class,
            [
                'attr' => [
                    'size' => 3,
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'discountInvoiceLabel',
            TextType::class,
            [
                'attr' => [
                    'placeholder' => 'Discount invoice label',
                ],
                'required' => false,
            ]
        );

        if ($client = $financial->getClient()) {
            if ($client->getClientType() === Client::TYPE_COMPANY) {
                $builder->add(
                    'clientCompanyName',
                    TextType::class,
                    [
                        'label' => 'Company name',
                    ]
                );

                if ($financial->getTemplateIncludeTaxInformation()) {
                    $builder->add(
                        'clientCompanyRegistrationNumber',
                        TextType::class,
                        [
                            'required' => false,
                            'label' => 'Company registration number',
                        ]
                    );

                    $builder->add(
                        'clientCompanyTaxId',
                        TextType::class,
                        [
                            'required' => false,
                            'label' => 'Company tax id',
                        ]
                    );
                }
            } else {
                $builder->add(
                    'clientFirstName',
                    TextType::class,
                    [
                        'label' => 'First name',
                    ]
                );

                $builder->add(
                    'clientLastName',
                    TextType::class,
                    [
                        'label' => 'Last name',
                    ]
                );
            }

            if ($financial->getClientInvoiceAddressSameAsContact()) {
                $builder->add(
                    'clientStreet1',
                    TextType::class,
                    [
                        'label' => 'Street',
                        'required' => false,
                    ]
                );
                $builder->add(
                    'clientStreet2',
                    TextType::class,
                    [
                        'required' => false,
                        'label' => 'Street 2',
                    ]
                );
                $builder->add(
                    'clientCity',
                    TextType::class,
                    [
                        'label' => 'City',
                        'required' => false,
                    ]
                );
                $builder->add(
                    'clientCountry',
                    CountryChoiceType::class,
                    [
                        'label' => 'Country',
                        'required' => false,
                    ]
                );
                $builder->add(
                    'clientState',
                    StateChoiceType::class,
                    [
                        'label' => 'State',
                    ]
                );
                $builder->add(
                    'clientZipCode',
                    TextType::class,
                    [
                        'label' => 'ZIP Code',
                        'required' => false,
                    ]
                );
            } else {
                $builder->add(
                    'clientInvoiceStreet1',
                    TextType::class,
                    [
                        'label' => 'Street',
                        'required' => false,
                    ]
                );
                $builder->add(
                    'clientInvoiceStreet2',
                    TextType::class,
                    [
                        'required' => false,
                        'label' => 'Street 2',
                    ]
                );
                $builder->add(
                    'clientInvoiceCity',
                    TextType::class,
                    [
                        'label' => 'City',
                        'required' => false,
                    ]
                );
                $builder->add(
                    'clientInvoiceCountry',
                    CountryChoiceType::class,
                    [
                        'label' => 'Country',
                        'required' => false,
                    ]
                );
                $builder->add(
                    'clientInvoiceState',
                    StateChoiceType::class,
                    [
                        'label' => 'State',
                        'required' => false,
                    ]
                );
                $builder->add(
                    'clientInvoiceZipCode',
                    TextType::class,
                    [
                        'label' => 'ZIP Code',
                        'required' => false,
                    ]
                );
            }
        }

        if ($organization = $financial->getOrganization()) {
            $builder->add(
                'organizationName',
                TextType::class,
                [
                    'label' => 'Name',
                ]
            );

            $builder->add(
                'organizationStreet1',
                TextType::class,
                [
                    'label' => 'Street',
                    'required' => false,
                ]
            );
            $builder->add(
                'organizationStreet2',
                TextType::class,
                [
                    'required' => false,
                    'label' => 'Street 2',
                ]
            );
            $builder->add(
                'organizationCity',
                TextType::class,
                [
                    'label' => 'City',
                    'required' => false,
                ]
            );
            $builder->add(
                'organizationCountry',
                CountryChoiceType::class,
                [
                    'label' => 'Country',
                    'required' => false,
                ]
            );
            $builder->add(
                'organizationState',
                StateChoiceType::class,
                [
                    'label' => 'State',
                    'required' => false,
                ]
            );
            $builder->add(
                'organizationZipCode',
                TextType::class,
                [
                    'label' => 'ZIP Code',
                    'required' => false,
                ]
            );

            if ($financial->getTemplateIncludeTaxInformation()) {
                $builder->add(
                    'organizationRegistrationNumber',
                    TextType::class,
                    [
                        'required' => false,
                        'label' => 'Registration Number',
                    ]
                );
                $builder->add(
                    'organizationTaxId',
                    TextType::class,
                    [
                        'required' => false,
                        'label' => 'Tax ID',
                    ]
                );
            }

            if ($financial->getTemplateIncludeBankAccount()) {
                $builder->add(
                    'organizationBankAccount',
                    EntityType::class,
                    [
                        'label' => 'Bank account',
                        'class' => OrganizationBankAccount::class,
                        'choice_label' => 'accountLabel',
                        'required' => false,
                        'query_builder' => function (EntityRepository $entityRepository) {
                            return $entityRepository->createQueryBuilder('oba')->orderBy('oba.name', 'ASC');
                        },
                        'mapped' => false,
                        'data' => $organization->getBankAccount(),
                    ]
                );
            }
        }

        $builder->add(
            'save',
            SubmitType::class
        );

        $builder->add(
            'sendAndSave',
            SubmitType::class
        );
    }
}
