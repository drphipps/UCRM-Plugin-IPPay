<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\DataProvider\ServiceDataProvider;
use AppBundle\Entity\Client;
use AppBundle\Entity\General;
use AppBundle\Entity\Option;
use AppBundle\Entity\PaymentPlan;
use AppBundle\Entity\Service;
use AppBundle\Entity\TariffPeriod;
use AppBundle\Service\Options;
use AppBundle\Service\ServiceCalculations;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PaymentPlanType extends AbstractType
{
    /**
     * @var ServiceCalculations
     */
    private $serviceCalculations;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var ServiceDataProvider
     */
    private $serviceDataProvider;

    public function __construct(
        ServiceCalculations $serviceCalculations,
        Options $options,
        ServiceDataProvider $serviceDataProvider
    ) {
        $this->serviceCalculations = $serviceCalculations;
        $this->options = $options;
        $this->serviceDataProvider = $serviceDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Client $client */
        $client = $options['client'];
        $organization = $client->getOrganization();
        $sandbox = (bool) $this->options->getGeneral(General::SANDBOX_MODE);
        $includeServiceSelect = $this->options->get(Option::SUBSCRIPTIONS_ENABLED_LINKED)
            && $organization->hasPaymentProviderSupportingAutopay($sandbox);
        $customSubscriptionEnabled = $this->options->get(Option::SUBSCRIPTIONS_ENABLED_CUSTOM);

        if ($includeServiceSelect) {
            $builder->add(
                'service',
                EntityType::class,
                [
                    'required' => ! $customSubscriptionEnabled,
                    'class' => Service::class,
                    'choice_label' => function (Service $service) {
                        return $service->getName();
                    },
                    'placeholder' => 'Make a choice.',
                    'query_builder' => function () use ($client) {
                        return $this->serviceDataProvider->getServicesForLinkedSubscriptionsQueryBuilder($client);
                    },
                    'choice_attr' => function (Service $service) {
                        return [
                            'data-period' => $service->getTariffPeriodMonths(),
                            'data-total-price' => $this->serviceCalculations->getTotalPrice($service),
                        ];
                    },
                    'constraints' => $customSubscriptionEnabled ? [] : [new NotBlank()],
                    'expanded' => true,
                ]
            );
        }

        $builder->add(
            'amountInSmallestUnit',
            FloatType::class,
            [
                'label' => 'Amount',
                'scale' => 2,
                'required' => true,
            ]
        );

        $builder->add(
            'period',
            ChoiceType::class,
            [
                'choices' => array_flip(TariffPeriod::PERIOD_REPLACE_STRING),
                'required' => true,
                'label' => 'Payment frequency',
            ]
        );

        $builder->get('amountInSmallestUnit')->addModelTransformer(
            new CallbackTransformer(
                function ($value) use ($options) {
                    return $value / $options['smallest_unit_multiplier'];
                },
                function ($value) use ($options) {
                    return (int) round($value * $options['smallest_unit_multiplier']);
                }
            )
        );

        $builder->add(
            'startDate',
            DateType::class,
            [
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
                'attr' => [
                    'data-datepicker-min-date' => (new \DateTime('today midnight'))->format('Y-m-d'),
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => PaymentPlan::class,
                'client' => null,
                'smallest_unit_multiplier' => 100,
            ]
        );

        $resolver->setAllowedTypes('client', Client::class);
        $resolver->setAllowedTypes('smallest_unit_multiplier', 'int');
        $resolver->setRequired('client');
        $resolver->setRequired('smallest_unit_multiplier');
    }
}
