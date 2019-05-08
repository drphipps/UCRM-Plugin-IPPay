<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Service;
use AppBundle\Entity\Tariff;
use AppBundle\Util\Formatter;
use AppBundle\Util\Invoicing;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceInvoiceInformationType extends AbstractType
{
    /**
     * @var Formatter
     */
    private $formatter;

    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Service $service */
        $service = $options['data'];

        $builder->add(
            'invoiceLabel',
            TextType::class
        );

        $builder->add(
            'invoicingPeriodType',
            ChoiceType::class,
            [
                'choices' => array_flip(Service::INVOICING_PERIOD_TYPE),
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
            ]
        );

        $builder->add(
            'invoicingLastPeriodEnd',
            ChoiceType::class,
            [
                'choices' => $this->createInvoicingLastPeriodEndChoiceList($service),
                'choice_translation_domain' => false,
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
            'individualPrice',
            FloatType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'tariff',
            TariffChoiceType::class,
            [
                'periodChangeDisabled' => $options['periodChangeDisabled'],
                'service' => $service,
            ]
        );

        $formModifier = function (FormInterface $form, ?Tariff $tariff = null) use ($service, $options) {
            $form->add(
                'tariffPeriod',
                TariffPeriodChoiceType::class,
                [
                    'service' => $service,
                    'tariff' => $tariff,
                    'disabled' => $options['periodChangeDisabled'],
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Service::class,
                'enableActiveFrom' => true,
                'periodChangeDisabled' => false,
            ]
        );

        $resolver->setAllowedTypes('enableActiveFrom', 'bool');
        $resolver->setAllowedTypes('periodChangeDisabled', 'boolean');
    }

    private function createInvoicingLastPeriodEndChoiceList(Service $service): array
    {
        $choices = [
            $this->formatter->formatDate($service->getInvoicingStart(), Formatter::DEFAULT, Formatter::NONE) => null,
        ];

        $since = sprintf('-%d months', $service->getTariffPeriod()->getPeriod() * 6);
        $default = $service->getInvoicingLastPeriodEnd();
        $periods = Invoicing::getInvoicedPeriods(
            $service,
            min($default, max($service->getInvoicingStart(), Invoicing::getDateUTC(new \DateTime($since))))
        );

        foreach ($periods as $period) {
            /** @var \DateTime|null $date */
            $date = $period['invoicedTo'];
            if (! $date) {
                continue;
            }
            if ($default && $date->format('Y-m-d') === $default->format('Y-m-d')) {
                $date = $default;
            }
            $key = $this->formatter->formatDate($date, Formatter::DEFAULT, Formatter::NONE);
            $choices[$key] = $date;
        }

        return $choices;
    }
}
