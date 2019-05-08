<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\BaseDevice;
use AppBundle\Entity\Device;
use AppBundle\Entity\Option;
use AppBundle\Entity\Site;
use AppBundle\Entity\Vendor;
use AppBundle\Service\Options;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class DeviceType extends AbstractType
{
    /**
     * @var Options
     */
    private $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $deviceId = $options['data']->getId();

        $qosDestinationGateway = $this->options->get(Option::QOS_ENABLED)
            && $this->options->get(Option::QOS_DESTINATION) === Option::QOS_DESTINATION_GATEWAY;

        $builder->add(
            'name',
            TextType::class,
            [
                'required' => true,
            ]
        );

        $builder->add(
            'vendor',
            VendorChoiceType::class,
            [
                'label' => 'Vendor',
                'required' => true,
                'choice_attr' => function ($key) {
                    $attributes = [];
                    if (! in_array($key, Vendor::QOS_VENDORS, true)) {
                        $attributes['data-qos-disallow'] = BaseDevice::QOS_THIS;
                    }
                    if (! in_array($key, Vendor::SUSPEND_VENDORS, true)) {
                        $attributes['data-suspend-disallow'] = 1;
                    }

                    return $attributes;
                },
                'constraints' => new NotNull(),
            ]
        );

        $builder->add(
            'parents',
            DeviceChoiceType::class,
            [
                'label' => 'Parent devices',
                'multiple' => true,
                'required' => false,
                'group_by' => function (Device $device) {
                    return $device->getSite()->getName();
                },
                'query_builder' => function (EntityRepository $repository) use ($deviceId) {
                    $qb = $repository->createQueryBuilder('d')
                        ->where('d.deletedAt IS NULL');

                    if ($deviceId) {
                        $qb->andWhere('d.id != :id')
                            ->setParameter('id', $deviceId);
                    }

                    return $qb->orderBy('d.name', 'ASC');
                },
            ]
        );

        $builder->add(
            'modelName',
            TextType::class,
            [
                'required' => false,
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
                'empty_data' => Device::DEFAULT_SSH_PORT,
            ]
        );

        $builder->add(
            'managementIpAddress',
            IpType::class,
            [
                'required' => false,
                'label' => 'Management IP',
            ]
        );

        $builder->add(
            'snmpCommunity',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'osVersion',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'notes',
            TextareaType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'site',
            EntityType::class,
            [
                'placeholder' => 'Make a choice.',
                'class' => Site::class,
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->andWhere('s.deletedAt IS NULL')
                        ->orderBy('s.name');
                },
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
            'createSignalStatistics',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'isGateway',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Device is gateway',
                'attr' => [
                    'data-qos-disallow' => Device::QOS_ANOTHER,
                ],
            ]
        );

        $builder->add(
            'isSuspendEnabled',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Suspend enabled',
            ]
        );

        $builder->add(
            'bandwidth',
            NumberType::class,
            [
                'required' => false,
            ]
        );

        if (! $qosDestinationGateway) {
            $builder->add(
                'qosEnabled',
                ChoiceType::class,
                [
                    'label' => 'QoS enabled',
                    'required' => true,
                    'choices' => array_flip(Device::QOS_TYPES),
                ]
            );

            $builder->add(
                'qosDevices',
                DeviceChoiceType::class,
                [
                    'multiple' => true,
                    'excludeDeviceId' => $deviceId,
                    'vendors' => [
                        Vendor::EDGE_OS,
                        // @todo Uncomment when UCRM-62 is implemented.
                        // Vendor::ROUTER_OS,
                    ],
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Device::class,
            ]
        );
    }
}
