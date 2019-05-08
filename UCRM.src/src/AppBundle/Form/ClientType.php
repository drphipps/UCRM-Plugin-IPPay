<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Component\Validator\Constraints\ClientIdMax;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientContact;
use AppBundle\Entity\ClientTag;
use AppBundle\Entity\Organization;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'userIdent',
            TextType::class,
            [
                'required' => false,
                'label' => 'Custom ID',
            ]
        );

        if ($options['includeOrganizationSelect']) {
            $builder->add(
                'organization',
                OrganizationChoiceType::class,
                [
                    'choice_attr' => function (Organization $organization) use ($options) {
                        /** @var Client $client */
                        $client = $options['data'];

                        return [
                            'data-invoice-maturity-days' => $organization->getInvoiceMaturityDays(),
                            'data-country-id' => $organization->getCountry()
                                ? $organization->getCountry()->getId()
                                : null,
                            'data-state-id' => $organization->getState()
                                ? $organization->getState()->getId()
                                : null,
                            'disabled' => $options['hasFinancialEntities']
                                && $client->getOrganization()
                                && $organization->getCurrency() !== $client->getOrganization()->getCurrency(),
                        ];
                    },
                ]
            );
        }

        $builder->add(
            'isCompany',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Company',
                'property_path' => 'clientType',
            ]
        );
        $builder->get('isCompany')->addModelTransformer(
            new CallbackTransformer(
                function (?int $clientType) {
                    return $clientType === Client::TYPE_COMPANY;
                },
                function (bool $value) {
                    return $value
                        ? Client::TYPE_COMPANY
                        : Client::TYPE_RESIDENTIAL;
                }
            )
        );

        $builder->add(
            'isLead',
            CheckboxType::class,
            [
                'required' => false,
                'disabled' => $options['disabledLeadChoice'],
            ]
        );

        $builder->add(
            'companyName',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Company name',
                ],
            ]
        );
        $builder->add(
            'companyRegistrationNumber',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Registration number',
                ],
            ]
        );
        $builder->add(
            'companyTaxId',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Tax ID',
                ],
            ]
        );
        $builder->add(
            'companyWebsite',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Website',
                ],
            ]
        );

        $builder->add(
            'clientTags',
            EntityType::class,
            [
                'multiple' => true,
                'class' => ClientTag::class,
                'choice_label' => 'name',
                'choice_attr' => function (ClientTag $clientTag) {
                    return [
                        'data-color-background' => $clientTag->getColorBackground(),
                        'data-color-text' => $clientTag->getColorText(),
                    ];
                },
                'attr' => [
                    'placeholder' => 'Add tags',
                ],
                'required' => false,
                'query_builder' => function (EntityRepository $repository) {
                    return $repository
                        ->createQueryBuilder('ct')
                        ->orderBy('ct.name', 'ASC');
                },
            ]
        );

        $builder->add(
            'street1',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Street',
                ],
            ]
        );
        $builder->add(
            'street2',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Street 2',
                ],
            ]
        );
        $builder->add(
            'city',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'City',
                ],
            ]
        );
        $builder->add(
            'country',
            CountryChoiceType::class,
            [
                'required' => false,
                'placeholder' => 'Choose a country',
            ]
        );
        $builder->add(
            'state',
            StateChoiceType::class,
            [
                'required' => false,
                'placeholder' => 'Choose a state',
            ]
        );
        $builder->add(
            'zipCode',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'ZIP code',
                ],
            ]
        );
        $builder->add(
            'addressGpsLat',
            NumberType::class,
            [
                'required' => false,
                'scale' => 12,
            ]
        );
        $builder->add(
            'addressGpsLon',
            NumberType::class,
            [
                'required' => false,
                'scale' => 12,
            ]
        );
        $zeroToNullTransformer = new CallbackTransformer(
            function (?float $value) {
                return $value === 0.0 ? null : $value;
            },
            function (?float $value) {
                return $value === 0.0 ? null : $value;
            }
        );
        $builder->get('addressGpsLat')->addModelTransformer($zeroToNullTransformer);
        $builder->get('addressGpsLon')->addModelTransformer($zeroToNullTransformer);
        $builder->add(
            'isAddressGpsCustom',
            HiddenType::class,
            [
                'required' => true,
            ]
        );
        $builder->get('isAddressGpsCustom')->addModelTransformer(
            new CallbackTransformer(
                function (bool $value): string {
                    return $value ? '1' : '0';
                },
                function (?string $value): bool {
                    return (bool) $value;
                }
            )
        );
        $builder->add(
            'resolveGps',
            ButtonType::class,
            [
                'label' => 'Resolve GPS',
            ]
        );

        $builder->add(
            'invoiceStreet1',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Street',
                ],
            ]
        );
        $builder->add(
            'invoiceStreet2',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Street 2',
                ],
            ]
        );
        $builder->add(
            'invoiceCity',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'City',
                ],
            ]
        );
        $builder->add(
            'invoiceCountry',
            CountryChoiceType::class,
            [
                'required' => false,
                'placeholder' => 'Choose a country',
            ]
        );
        $builder->add(
            'invoiceState',
            StateChoiceType::class,
            [
                'required' => false,
                'placeholder' => 'Choose a state',
            ]
        );
        $builder->add(
            'invoiceZipCode',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'ZIP code',
                ],
            ]
        );
        $builder->add(
            'invoiceAddressSameAsContact',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );
        $builder->add(
            'note',
            TextareaType::class,
            [
                'required' => false,
                'attr' => [
                    'rows' => 1,
                    'placeholder' => 'Note',
                ],
            ]
        );
        $builder->add(
            'previousIsp',
            TextType::class,
            [
                'required' => false,
                'label' => 'Previous ISP',
            ]
        );
        $builder->add(
            'sendInvoiceByPost',
            NullableCheckboxType::class,
            [
                'required' => false,
            ]
        );
        $builder->add(
            'invoiceMaturityDays',
            IntegerType::class,
            [
                'required' => false,
            ]
        );
        $builder->add(
            'stopServiceDue',
            NullableCheckboxType::class,
            [
                'required' => false,
                'label' => 'Suspend services if payment overdue',
            ]
        );
        $builder->add(
            'stopServiceDueDays',
            IntegerType::class,
            [
                'required' => false,
                'label' => 'Suspension delay',
            ]
        );
        $builder->add(
            'lateFeeDelayDays',
            IntegerType::class,
            [
                'required' => false,
                'label' => 'Late fee delay',
            ]
        );
        $builder->add(
            'generateProformaInvoices',
            NullableCheckboxType::class,
            [
                'required' => false,
            ]
        );
        $builder->add('user', ClientUserType::class);
        $builder->add(
            'contacts',
            CollectionType::class,
            [
                'entry_type' => ClientContactType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ]
        );
        $builder->add(
            'registrationDate',
            DateType::class,
            [
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
            ]
        );

        // taxes
        $builder->add(
            'tax1',
            TaxChoiceType::class,
            [
                'required' => false,
            ]
        );
        if ($options['multipleTaxes']) {
            $builder->add(
                'tax2',
                TaxChoiceType::class,
                [
                    'required' => false,
                ]
            );
            $builder->add(
                'tax3',
                TaxChoiceType::class,
                [
                    'required' => false,
                ]
            );
        }

        $builder->add(
            'companyContactFirstName',
            TextType::class,
            [
                'required' => false,
                'label' => 'Contact first name',
                'attr' => [
                    'placeholder' => 'Contact first name',
                ],
            ]
        );
        $builder->add(
            'companyContactLastName',
            TextType::class,
            [
                'required' => false,
                'label' => 'Contact last name',
                'attr' => [
                    'placeholder' => 'Contact last name',
                ],
            ]
        );

        $builder->add(
            'attributes',
            ClientAttributesType::class,
            [
                'client' => $options['data'],
            ]
        );

        if ($options['firstClient']) {
            $builder->add(
                'clientId',
                IntegerType::class,
                [
                    'mapped' => false,
                    'label' => 'ID',
                    'required' => false,
                    'constraints' => [
                        new ClientIdMax(),
                        new LessThanOrEqual(2147483647),
                    ],
                    'attr' => [
                        'placeholder' => 'First client ID',
                    ],
                ]
            );
        }

        if (! $options['data']->getId()) {
            $builder->add(
                'save',
                SubmitType::class
            );
            $builder->add(
                'sendAndSave',
                SubmitType::class
            );
        }

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                /** @var Client $client */
                $client = $event->getForm()->getData();
                if ($client->getUser()->getUsername()) {
                    return;
                }

                $contacts = $client->getContacts();
                $loginContact = $contacts->filter(
                    function (ClientContact $contact) {
                        return $contact->getIsLogin() && $contact->getEmail();
                    }
                )->first();

                if ($loginContact) {
                    $client->getUser()->setUsername($loginContact->getEmail());
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Client::class,
                'multipleTaxes' => true,
                'firstClient' => true,
                'hasFinancialEntities' => false,
                'disabledLeadChoice' => false,
                'includeOrganizationSelect' => true,
            ]
        );

        $resolver->setAllowedTypes('multipleTaxes', 'bool');
        $resolver->setAllowedTypes('firstClient', 'bool');
        $resolver->setRequired('hasFinancialEntities');
        $resolver->setAllowedTypes('hasFinancialEntities', 'bool');
        $resolver->setAllowedTypes('disabledLeadChoice', 'bool');
        $resolver->setAllowedTypes('includeOrganizationSelect', 'bool');
    }
}
