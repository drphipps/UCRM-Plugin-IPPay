<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\DeviceInterfaceIp;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeviceInterfaceIpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'ipRange',
                IpRangeType::class,
                [
                    'required' => true,
                ]
            )
            ->add(
                'isAccessible',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            );

        // Needed for usage in CollectionType to prevent calling a nonexistent
        // method DeviceInterfaceIp::setIpRange().
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                if (! $event->getForm()->getData()) {
                    $event->getForm()->setData(new DeviceInterfaceIp());
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => DeviceInterfaceIp::class,
            ]
        );
    }
}
