<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Service;
use AppBundle\Entity\Tariff;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceChangeTariffType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $service = $options['data'];
        assert($service instanceof Service);

        $builder->add(
            'name',
            TextType::class,
            [
                'required' => false,
                'property_path' => 'nameDirectly',
                'label' => 'Custom name',
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
        $builder->add(
            'individualPrice',
            FloatType::class,
            [
                'required' => false,
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Service::class,
                'periodChangeDisabled' => false,
            ]
        );

        $resolver->setAllowedTypes('periodChangeDisabled', 'boolean');
    }
}
