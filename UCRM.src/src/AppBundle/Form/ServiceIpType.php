<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\ServiceIp;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceIpType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'ipRange',
                IpRangeType::class,
                [
                    'label' => 'IP Address',
                    'required' => true,
                ]
            );

        // Needed for usage in CollectionType to prevent calling a nonexistent
        // method ServiceIp::setIpRange() and to set ServiceDevice.
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                if (! $event->getForm()->getData()) {
                    /** @var ServiceDevice $serviceDevice */
                    $serviceDevice = $event->getForm()->getParent()->getParent()->getData();
                    $serviceIp = new ServiceIp();
                    $serviceIp->setServiceDevice($serviceDevice);
                    $event->getForm()->setData($serviceIp);
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => ServiceIp::class,
            ]
        );
    }
}
