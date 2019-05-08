<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Organization;
use AppBundle\Entity\OrganizationBankAccount;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class OrganizationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Organization $organization */
        $organization = $options['data'];

        $builder->add('name');
        $builder->add('registrationNumber');
        $builder->add('taxId');
        $builder->add('website');
        $builder->add('phone');
        $builder->add('email', EmailType::class);
        $builder->add('street1');
        $builder->add('street2');
        $builder->add('city', TextType::class);
        $builder->add('country', CountryChoiceType::class);
        $builder->add(
            'state',
            StateChoiceType::class,
            [
                'placeholder' => 'Choose a state',
            ]
        );
        $builder->add('zipCode', TextType::class);
        $builder->add('invoiceMaturityDays', IntegerType::class);

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
        $builder->add(
            'bankAccount',
            EntityType::class,
            [
                'class' => OrganizationBankAccount::class,
                'choice_label' => 'accountLabel',
                'placeholder' => 'Make a choice.',
                'required' => false,
                'query_builder' => function (EntityRepository $entityRepository) {
                    return $entityRepository->createQueryBuilder('oba')->orderBy('oba.name', 'ASC');
                },
            ]
        );

        if ($options['sandbox']) {
            $builder->add(
                'payPalSandboxClientId',
                TextType::class,
                [
                    'label' => 'Sandbox Client ID',
                    'required' => false,
                ]
            );
            $builder->add(
                'payPalSandboxClientSecret',
                TextType::class,
                [
                    'label' => 'Sandbox Client secret',
                    'required' => false,
                ]
            );
        }
        $builder->add(
            'payPalLiveClientId',
            TextType::class,
            [
                'label' => 'Live Client ID',
                'required' => false,
            ]
        );
        $builder->add(
            'payPalLiveClientSecret',
            TextType::class,
            [
                'label' => 'Live Client secret',
                'required' => false,
            ]
        );

        if ($options['sandbox']) {
            $builder->add(
                'stripeTestSecretKey',
                TextType::class,
                [
                    'label' => 'Test Secret key',
                    'required' => false,
                ]
            );
            $builder->add(
                'stripeTestPublishableKey',
                TextType::class,
                [
                    'label' => 'Test Publishable key',
                    'required' => false,
                ]
            );
        }
        $builder->add(
            'stripeLiveSecretKey',
            TextType::class,
            [
                'label' => 'Live Secret key',
                'required' => false,
            ]
        );
        $builder->add(
            'stripeLivePublishableKey',
            TextType::class,
            [
                'label' => 'Live Publishable key',
                'required' => false,
            ]
        );
        $builder->add(
            'stripeAchEnabled',
            CheckboxType::class,
            [
                'label' => 'Stripe ACH',
                'required' => false,
            ]
        );
        $builder->add(
            'stripeImportUnattachedPayments',
            CheckboxType::class,
            [
                'label' => 'Import unattached payments',
                'required' => false,
            ]
        );

        if ($options['sandbox']) {
            $builder->add(
                'anetSandboxLoginId',
                TextType::class,
                [
                    'label' => 'Sandbox Login ID',
                    'required' => false,
                ]
            );
            $builder->add(
                'anetSandboxTransactionKey',
                TextType::class,
                [
                    'label' => 'Sandbox Transaction key',
                    'required' => false,
                ]
            );
            $builder->add(
                'anetSandboxHash',
                TextType::class,
                [
                    'label' => 'Sandbox MD5 hash key',
                    'required' => false,
                ]
            );
            $builder->add(
                'anetSandboxSignatureKey',
                TextType::class,
                [
                    'label' => 'Sandbox Signature Key',
                    'required' => false,
                ]
            );
        }
        $builder->add(
            'anetLiveLoginId',
            TextType::class,
            [
                'label' => 'Live Login ID',
                'required' => false,
            ]
        );
        $builder->add(
            'anetLiveTransactionKey',
            TextType::class,
            [
                'label' => 'Live Transaction key',
                'required' => false,
            ]
        );
        $builder->add(
            'anetLiveHash',
            TextType::class,
            [
                'label' => 'Live MD5 hash key',
                'required' => false,
            ]
        );
        $builder->add(
            'anetLiveSignatureKey',
            TextType::class,
            [
                'label' => 'Live Signature Key',
                'required' => false,
            ]
        );

        if ($options['sandbox']) {
            $builder->add(
                'ipPaySandboxUrl',
                TextType::class,
                [
                    'label' => 'Testing URL',
                    'required' => false,
                ]
            );
            $builder->add(
                'ipPaySandboxTerminalId',
                TextType::class,
                [
                    'label' => 'Testing terminal ID',
                    'required' => false,
                ]
            );
            $builder->add(
                'ipPaySandboxMerchantCurrency',
                CurrencyChoiceType::class,
                [
                    'label' => 'Sandbox Merchant Currency',
                    'required' => false,
                    'organization' => $options['organization'],
                ]
            );
        }
        $builder->add(
            'ipPayLiveUrl',
            TextType::class,
            [
                'label' => 'Production URL',
                'required' => false,
            ]
        );
        $builder->add(
            'ipPayLiveTerminalId',
            TextType::class,
            [
                'label' => 'Terminal ID',
                'required' => false,
            ]
        );
        $builder->add(
            'ipPayLiveMerchantCurrency',
            CurrencyChoiceType::class,
            [
                'label' => 'Merchant Currency',
                'required' => false,
                'organization' => $options['organization'],
            ]
        );

        $builder->add(
            'mercadoPagoClientId',
            TextType::class,
            [
                'label' => 'Client ID',
                'required' => false,
            ]
        );
        $builder->add(
            'mercadoPagoClientSecret',
            TextType::class,
            [
                'label' => 'Client secret',
                'required' => false,
            ]
        );

        $builder->add(
            'fileLogo',
            FileType::class,
            [
                'required' => false,
                'label' => 'Logo',
            ]
        );
        $builder->add(
            'fileStamp',
            FileType::class,
            [
                'required' => false,
                'label' => 'Stamp',
            ]
        );
        $builder->add(
            'invoiceTemplate',
            InvoiceTemplateChoiceType::class,
            [
                'selectedInvoiceTemplate' => $organization->getInvoiceTemplate(),
            ]
        );
        $builder->add(
            'proformaInvoiceTemplate',
            ProformaInvoiceTemplateChoiceType::class,
            [
                'selectedProformaInvoiceTemplate' => $organization->getProformaInvoiceTemplate(),
            ]
        );
        $builder->add(
            'quoteTemplate',
            QuoteTemplateChoiceType::class,
            [
                'selectedQuoteTemplate' => $organization->getQuoteTemplate(),
            ]
        );
        $builder->add(
            'accountStatementTemplate',
            AccountStatementTemplateChoiceType::class,
            [
                'selectedAccountStatementTemplate' => $organization->getAccountStatementTemplate(),
            ]
        );
        $builder->add(
            'accountStatementTemplateIncludeBankAccount',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Include bank account',
            ]
        );
        $builder->add(
            'accountStatementTemplateIncludeTaxInformation',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Include tax information',
            ]
        );

        $builder->add(
            'paymentReceiptTemplate',
            PaymentReceiptTemplateChoiceType::class,
            [
                'selectedPaymentReceiptTemplate' => $organization->getPaymentReceiptTemplate(),
            ]
        );
        $builder->add(
            'invoiceTemplateIncludeBankAccount',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Include bank account',
            ]
        );
        $builder->add(
            'invoiceTemplateIncludeTaxInformation',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Include tax information',
            ]
        );
        $builder->add(
            'invoiceTemplateDefaultNotes',
            TextareaType::class,
            [
                'required' => false,
                'attr' => [
                    'rows' => 4,
                ],
            ]
        );
        $builder->add(
            'quoteTemplateDefaultNotes',
            TextareaType::class,
            [
                'required' => false,
                'attr' => [
                    'rows' => 4,
                ],
            ]
        );
        $builder->add(
            'quoteTemplateIncludeBankAccount',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Include bank account',
            ]
        );
        $builder->add(
            'quoteTemplateIncludeTaxInformation',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Include tax information',
            ]
        );

        $builder->add(
            'quoteNumberPrefix',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'quoteNumberLength',
            ChoiceType::class,
            [
                'required' => true,
                'choices' => array_combine(range(1, 20), range(1, 20)),
                'choice_translation_domain' => false,
            ]
        );

        $builder->add(
            'quoteInitNumber',
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
            'proformaInvoiceNumberPrefix',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'proformaInvoiceNumberLength',
            ChoiceType::class,
            [
                'required' => true,
                'choices' => array_combine(range(1, 20), range(1, 20)),
                'choice_translation_domain' => false,
            ]
        );

        $builder->add(
            'proformaInvoiceInitNumber',
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
            'receiptNumberPrefix',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'receiptNumberLength',
            ChoiceType::class,
            [
                'required' => true,
                'choices' => array_combine(range(1, 20), range(1, 20)),
                'choice_translation_domain' => false,
            ]
        );

        $builder->add(
            'receiptInitNumber',
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
            'locale',
            LocaleType::class,
            [
                'required' => false,
                'label' => 'Currency language',
                'attr' => [
                    'placeholder' => 'Make a choice.',
                ],
            ]
        );

        $builder->add(
            'invoicedTotalRoundingPrecision',
            IntegerType::class,
            [
                'required' => false,
                'label' => 'Rounding precision',
            ]
        );

        $builder->add(
            'roundingTotalEnabled',
            CheckboxType::class,
            [
                'label' => 'Custom total price rounding',
                'required' => false,
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

        $resolver->setRequired('sandbox');
        $resolver->setRequired('hasFinancialEntities');
        $resolver->setAllowedTypes('hasFinancialEntities', 'bool');
        $resolver->setRequired('organization');
        $resolver->setAllowedTypes('organization', Organization::class);
    }
}
