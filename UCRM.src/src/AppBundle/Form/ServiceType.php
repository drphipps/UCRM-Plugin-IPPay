<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Component\Validator\Constraints\Mac;
use AppBundle\Entity\Service;
use AppBundle\Entity\Tariff;
use AppBundle\Util\Arrays;
use AppBundle\Util\DateTimeFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class ServiceType extends AbstractType
{
    public const ACTIVATION_NOW = 'now';
    public const ACTIVATION_CUSTOM = 'custom';
    public const ACTIVATION_QUOTED = 'quoted';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Service $service */
        $service = $options['data'];

        $activationChoices = [
            'Activate now' => self::ACTIVATION_NOW,
            'Activate on date...' => self::ACTIVATION_CUSTOM,
            'Keep inactive (quoted) for now' => self::ACTIVATION_QUOTED,
        ];
        if (! $options['activation_enable_now']) {
            unset($activationChoices['Activate now']);
        }
        if (! $options['activation_enable_quoted']) {
            unset($activationChoices['Keep inactive (quoted) for now']);
        }

        if (count($activationChoices) > 1) {
            $builder->add(
                'activation',
                ChoiceType::class,
                [
                    'mapped' => false,
                    'choices' => $activationChoices,
                    'choice_attr' => function (string $value) {
                        $statusClass = '';
                        switch ($value) {
                            case self::ACTIVATION_NOW:
                                $statusClass = 'success';
                                break;
                            case self::ACTIVATION_CUSTOM:
                                $statusClass = 'success-o';
                                break;
                            case self::ACTIVATION_QUOTED:
                                $statusClass = 'warning';
                                break;
                        }

                        return [
                            'data-status-class' => $statusClass,
                        ];
                    },
                ]
            );
        }

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
            'fccBlockId',
            TextType::class,
            [
                'required' => false,
            ]
        );
        $builder->add(
            'activeFrom',
            DateType::class,
            [
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
                'disabled' => ! $options['enableActiveFrom'],
                'attr' => [
                    'placeholder' => 'Active from',
                    'data-current-date' => DateTimeFactory::createWithoutFormat('now')->format('Y-m-d'),
                ],
            ]
        );
        $builder->add(
            'blockPrepared',
            CheckboxType::class,
            [
                'label' => 'Block service IPs until the service is activated',
                'required' => false,
                'mapped' => false,
                'data' => $service->getStatus() === Service::STATUS_PREPARED_BLOCKED
                    && null !== $service->getStopReason(),
            ]
        );
        $builder->add(
            'activeTo',
            DateType::class,
            [
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'disabled' => $service->getStatus() === Service::STATUS_ENDED,
                'attr' => [
                    'placeholder' => 'Active to',
                ],
            ]
        );

        $invoicingStartOptions = [
            'required' => true,
            'widget' => 'single_text',
            'html5' => false,
        ];

        if ($options['deferredChange']) {
            $date = Arrays::max(
                [
                    new \DateTime('midnight'),
                    clone $service->getActiveFrom(),
                    $service->getInvoicingLastPeriodEnd()
                        ? (clone $service->getInvoicingLastPeriodEnd())->modify('+1 day')
                        : null,
                ]
            );
            $invoicingStartOptions['constraints'] = new GreaterThanOrEqual($date);
            $invoicingStartOptions['attr'] = [
                'data-datepicker-min-date' => ($date)->format('Y-m-d'),
            ];
        }

        $builder->add(
            'invoicingStart',
            DateType::class,
            $invoicingStartOptions
        );
        $builder->add(
            'tariff',
            TariffChoiceType::class,
            [
                'service' => $service,
            ]
        );
        $builder->add(
            'name',
            TextType::class,
            [
                'required' => false,
                'label' => 'Custom name',
                'attr' => [
                    'placeholder' => 'Custom name',
                ],
                'property_path' => 'nameDirectly',
            ]
        );
        $builder->add(
            'individualPrice',
            FloatType::class,
            [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Individual price',
                ],
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
        // invoice
        $builder->add(
            'invoiceLabel',
            TextType::class,
            [
                'required' => false,
                'label' => 'Invoice item label',
            ]
        );
        $builder->add(
            'invoicingPeriodType',
            ChoiceType::class,
            [
                'choices' => array_flip(Service::INVOICING_PERIOD_TYPE),
            ]
        );
        $builder->add(
            'invoicingPeriodStartDay',
            InvoicePeriodStartDayType::class,
            [
                'invoice_period_start_day_choices' => Service::INVOICING_PERIOD_START_DAY,
            ]
        );
        $builder->add(
            'nextInvoicingDayAdjustment',
            IntegerType::class,
            [
                'attr' => [
                    'min' => 0,
                ],
            ]
        );
        $builder->add(
            'invoicingSeparately',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );
        $builder->add(
            'sendEmailsAutomatically',
            NullableCheckboxType::class,
            [
                'label' => 'Approve and send invoice automatically',
                'required' => false,
            ]
        );
        $builder->add(
            'useCreditAutomatically',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );
        $builder->add(
            'invoicingProratedSeparately',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Pro-rated separately',
            ]
        );

        // taxes
        $builder->add(
            'tax1',
            TaxChoiceType::class,
            [
                'required' => false,
                'attr' => [
                    'data-default' => ! $service->getId() && $service->getClient()->getTax1()
                        ? $service->getClient()->getTax1()->getId()
                        : null,
                ],
                'disabled' => $service->getTariff()
                    && $service->getTariff()->getTaxable()
                    && $service->getTariff()->getTax(),
            ]
        );
        if ($options['multipleTaxes']) {
            $builder->add(
                'tax2',
                TaxChoiceType::class,
                [
                    'required' => false,
                    'attr' => [
                        'data-default' => ! $service->getId() && $service->getClient()->getTax2()
                            ? $service->getClient()->getTax2()->getId()
                            : null,
                    ],
                    'disabled' => $service->getTariff()
                        && $service->getTariff()->getTaxable()
                        && $service->getTariff()->getTax(),
                ]
            );
            $builder->add(
                'tax3',
                TaxChoiceType::class,
                [
                    'required' => false,
                    'attr' => [
                        'data-default' => ! $service->getId() && $service->getClient()->getTax3()
                            ? $service->getClient()->getTax3()->getId()
                            : null,
                    ],
                    'disabled' => $service->getTariff()
                        && $service->getTariff()->getTaxable()
                        && $service->getTariff()->getTax(),
                ]
            );
        }

        //contract
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
                'label' => 'Minimum contract length',
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

        // surcharge
        $builder->add(
            'serviceSurcharges',
            CollectionType::class,
            [
                'entry_type' => ServiceSurchargeType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ]
        );
        // discount
        $builder->add(
            'discountType',
            ChoiceType::class,
            [
                'choices' => array_flip(Service::DISCOUNT_TYPE),
            ]
        );
        $builder->add(
            'discountValue',
            FloatType::class,
            [
                'required' => false,
            ]
        );
        $builder->add(
            'discountInvoiceLabel',
            TextType::class,
            [
                'required' => false,
            ]
        );
        $builder->add(
            'discountFrom',
            DateType::class,
            [
                'widget' => 'single_text',
                'attr' => [
                    'style' => 'display:none',
                ],
                'required' => false,
            ]
        );
        $builder->add(
            'discountTo',
            DateType::class,
            [
                'widget' => 'single_text',
                'attr' => [
                    'style' => 'display:none',
                ],
                'required' => false,
            ]
        );

        $formModifier = function (FormInterface $form, Tariff $tariff = null) use ($service) {
            $form->add(
                'tariffPeriod',
                TariffPeriodChoiceType::class,
                [
                    'service' => $service,
                    'tariff' => $tariff,
                ]
            );
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                /** @var Service|null $data */
                $data = $event->getData();
                $formModifier($event->getForm(), $data ? $data->getTariff() : null);
            }
        );

        $builder->get('tariff')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $form = $event->getForm();
                $formModifier($form->getParent(), $form->getData());
            }
        );

        if (! $service->getId()) {
            $builder->add(
                'serviceDevice',
                ServiceToServiceDeviceType::class,
                [
                    'required' => false,
                    'mapped' => false,
                    'client' => $service->getClient(),
                    'allow_null' => true,
                ]
            );
            $builder->add(
                'deviceInterface',
                DeviceInterfaceChoiceType::class,
                [
                    'required' => false,
                    'mapped' => false,
                ]
            );
            $builder->add(
                'ipRange',
                IpRangeType::class,
                [
                    'label' => 'IP Address',
                    'required' => false,
                    'mapped' => false,
                ]
            );
            $builder->add(
                'vendor',
                VendorChoiceType::class,
                [
                    'mapped' => false,
                    'required' => false,
                    'placeholder' => 'Make a choice.',
                ]
            );
            $builder->add(
                'macAddress',
                TextType::class,
                [
                    'mapped' => false,
                    'required' => false,
                    'constraints' => new Mac(),
                    'attr' => [
                        'maxlength' => 17,
                        'pattern' => Mac::PATTERN_INPUT,
                    ],
                ]
            );
            $builder->add(
                'sendPingNotifications',
                CheckboxType::class,
                [
                    'label' => 'Send outage notifications to',
                    'mapped' => false,
                    'required' => false,
                    'data' => false,
                ]
            );
            $builder->add(
                'pingNotificationUser',
                AdminChoiceType::class,
                [
                    'required' => false,
                    'mapped' => false,
                    'user' => null,
                ]
            );
            $builder->add(
                'createPingStatistics',
                CheckboxType::class,
                [
                    'mapped' => false,
                    'required' => false,
                    'data' => true,
                ]
            );

            $builder->add(
                'save',
                SubmitType::class
            );

            $builder->add(
                'saveAndQuote',
                SubmitType::class
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Service::class,
                'enableActiveFrom' => true,
                'deferredChange' => false,
                'multipleTaxes' => true,
                'enableSetupFee' => false,
                'activation_enable_now' => false,
                'activation_enable_quoted' => false,
            ]
        );

        $resolver->setAllowedTypes('enableActiveFrom', 'bool');
        $resolver->setAllowedTypes('deferredChange', 'bool');
        $resolver->setAllowedTypes('multipleTaxes', 'bool');
        $resolver->setAllowedTypes('enableSetupFee', 'bool');
        $resolver->setAllowedTypes('activation_enable_now', 'bool');
        $resolver->setAllowedTypes('activation_enable_quoted', 'bool');
    }
}
