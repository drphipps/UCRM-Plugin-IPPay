<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\DeviceInterface;
use AppBundle\Entity\DeviceInterfaceIp;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeviceInterfaceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add(
                'type',
                ChoiceType::class,
                [
                    'choices' => [
                        'Wireless' => DeviceInterface::TYPE_WIRELESS,
                        'Ethernet' => DeviceInterface::TYPE_ETHERNET,
                        'Vlan' => DeviceInterface::TYPE_VLAN,
                        'Mesh' => DeviceInterface::TYPE_MESH,
                        'Bonding' => DeviceInterface::TYPE_BONDING,
                        'Bridge' => DeviceInterface::TYPE_BRIDGE,
                        'CAP' => DeviceInterface::TYPE_CAP,
                        'GRE' => DeviceInterface::TYPE_GRE,
                        'GRE6' => DeviceInterface::TYPE_GRE6,
                        'L2TP' => DeviceInterface::TYPE_L2TP,
                        'OVPN' => DeviceInterface::TYPE_OVPN,
                        'PPPoE' => DeviceInterface::TYPE_PPPOE,
                        'PPTP' => DeviceInterface::TYPE_PPTP,
                        'SSTP' => DeviceInterface::TYPE_SSTP,
                        'VPLS' => DeviceInterface::TYPE_VPLS,
                        'Traffic eng' => DeviceInterface::TYPE_TRAFFIC_ENG,
                        'VRRP' => DeviceInterface::TYPE_VRRP,
                        'WDS' => DeviceInterface::TYPE_WDS,
                    ],
                ]
            )
            ->add(
                'macAddress',
                TextType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'allowClientConnection',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'notes',
                TextareaType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'enabled',
                CheckboxType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'ssid',
                TextType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'frequency',
                TextType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'polarization',
                ChoiceType::class,
                [
                    'choices' => [
                        'Vertical' => DeviceInterface::POLARIZATION_VERTICAL,
                        'Horizontal' => DeviceInterface::POLARIZATION_HORIZONTAL,
                        'Both' => DeviceInterface::POLARIZATION_BOTH,
                    ],
                    'empty_data' => DeviceInterface::POLARIZATION_BOTH,
                ]
            )
            ->add(
                'encryptionType',
                ChoiceType::class,
                [
                    'choices' => [
                        'None' => DeviceInterface::ENCRYPTION_TYPE_NONE,
                        'WEP' => DeviceInterface::ENCRYPTION_TYPE_WEP,
                        'WPA' => DeviceInterface::ENCRYPTION_TYPE_WPA,
                        'WPA2EAP' => DeviceInterface::ENCRYPTION_TYPE_WPA2EAP,
                        'WPA2PSK' => DeviceInterface::ENCRYPTION_TYPE_WPA2PSK,
                        'WPA2PSK / WPA2EAP' => DeviceInterface::ENCRYPTION_TYPE_WPA2PSK_WPA2EAP,
                        'WPA2PSK / WPAEAP' => DeviceInterface::ENCRYPTION_TYPE_WPA2PSK_WPAEAP,
                        'WPA2PSK / WPAEAP / WPA2EAP' => DeviceInterface::ENCRYPTION_TYPE_WPA2PSK_WPAEAP_WPA2EAP,
                        'WPAEAP' => DeviceInterface::ENCRYPTION_TYPE_WPAEAP,
                        'WPAEAP / WPA2EAP' => DeviceInterface::ENCRYPTION_TYPE_WPAEAP_WPA2EAP,
                        'WPAPSK' => DeviceInterface::ENCRYPTION_TYPE_WPAPSK,
                        'WPAPSK / WPA2EAP' => DeviceInterface::ENCRYPTION_TYPE_WPAPSK_WPA2EAP,
                        'WPAPSK / WPA2PSK' => DeviceInterface::ENCRYPTION_TYPE_WPAPSK_WPA2PSK,
                        'WPAPSK / WPA2PSK / WPA2EAP' => DeviceInterface::ENCRYPTION_TYPE_WPAPSK_WPA2PSK_WPA2EAP,
                        'WPAPSK / WPA2PSK / WPAEAP' => DeviceInterface::ENCRYPTION_TYPE_WPAPSK_WPA2PSK_WPAEAP,
                        'WPAPSK / WPA2PSK / WPAEAP / WPA2EAP' => DeviceInterface::ENCRYPTION_TYPE_WPAPSK_WPA2PSK_WPAEAP_WPA2EAP,
                        'WPAPSK / WPAEAP' => DeviceInterface::ENCRYPTION_TYPE_WPAPSK_WPAEAP,
                        'WPAPSK / WPAEAP / WPA2EAP' => DeviceInterface::ENCRYPTION_TYPE_WPAPSK_WPAEAP_WPA2EAP,
                    ],
                    'empty_data' => DeviceInterface::ENCRYPTION_TYPE_NONE,
                ]
            )
            ->add(
                'encryptionKeyWpa',
                TextType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'encryptionKeyWpa2',
                TextType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'interfaceIps',
                CollectionType::class,
                [
                    'entry_type' => DeviceInterfaceIpType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'prototype_data' => new DeviceInterfaceIp(),
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => DeviceInterface::class,
            ]
        );
    }
}
