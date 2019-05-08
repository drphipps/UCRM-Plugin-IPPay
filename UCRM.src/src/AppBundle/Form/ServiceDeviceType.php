<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Component\Validator\Constraints\Mac;
use AppBundle\Entity\Option;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\Vendor;
use AppBundle\Service\Options;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceDeviceType extends AbstractType
{
    /**
     * @var Options
     */
    private $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $qosDestinationGateway = $this->options->get(Option::QOS_ENABLED)
            && $this->options->get(Option::QOS_DESTINATION) === Option::QOS_DESTINATION_GATEWAY;

        $builder->add(
            'macAddress',
            TextType::class,
            [
                'required' => false,
                'attr' => [
                    'pattern' => Mac::PATTERN_INPUT,
                ],
            ]
        );

        $builder->add(
            'vendorId',
            ChoiceType::class,
            [
                'label' => 'Vendor',
                'mapped' => false,
                'required' => true,
                'data' => $options['vendorId'],
                'choices' => $options['vendors'],
            ]
        );

        $builder->add(
            'loginUsername',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'loginPassword',
            PasswordType::class,
            [
                'required' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
            ]
        );

        $builder->add(
            'sshPort',
            IntegerType::class,
            [
                'label' => 'SSH port',
                'required' => false,
                'empty_data' => ServiceDevice::DEFAULT_SSH_PORT,
            ]
        );

        $builder->add(
            'managementIpAddress',
            IpType::class,
            [
                'label' => 'Management IP',
                'required' => false,
            ]
        );

        $builder->add(
            'createSignalStatistics',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'sendPingNotifications',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Send outage notifications',
            ]
        );

        $builder->add(
            'pingNotificationUser',
            AdminChoiceType::class,
            [
                'required' => false,
                'label' => 'Send outage notifications to',
                'user' => isset($options['data']) ? $options['data']->getPingNotificationUser() : null,
            ]
        );

        $builder->add(
            'createPingStatistics',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'interface',
            DeviceInterfaceChoiceType::class,
            [
                'deviceInterface' => $builder->getData() ? $builder->getData()->getInterface() : null,
            ]
        );

        $builder->add(
            'serviceIps',
            CollectionType::class,
            [
                'entry_type' => ServiceIpType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ]
        );

        if (! $qosDestinationGateway) {
            $builder->add(
                'qosEnabled',
                ChoiceType::class,
                [
                    'label' => 'QoS enabled',
                    'required' => true,
                    'choices' => array_flip(ServiceDevice::QOS_TYPES),
                ]
            );

            $builder->add(
                'qosDevices',
                DeviceChoiceType::class,
                [
                    'multiple' => true,
                    'vendors' => [
                        Vendor::EDGE_OS,
                        // @todo Uncomment when UCRM-62 is implemented.
                        // Vendor::ROUTER_OS,
                    ],
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => ServiceDevice::class,
                'vendors' => [],
                'vendorId' => [],
            ]
        );
    }
}
