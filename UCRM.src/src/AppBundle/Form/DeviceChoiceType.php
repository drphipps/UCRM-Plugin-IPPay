<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Device;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class DeviceChoiceType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'label' => 'Device',
                'required' => false,
                'class' => Device::class,
                'placeholder' => 'Make a choice.',
                'attr' => [
                    'placeholder' => 'Make a choice.',
                ],
                'choice_label' => 'nameWithSite',
                'group_by' => function (Device $device) {
                    if ($device->isGateway()) {
                        return $this->translator->trans('Gateways');
                    }

                    return $this->translator->trans('Other devices');
                },
                'excludeDeviceId' => null,
                'excludeNetFlowDevices' => false,
                'excludeNonRouterDevices' => false,
                'vendors' => null,
            ]
        );

        $resolver->setDefault(
            'query_builder',
            function (Options $options) {
                return function (EntityRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('d')
                        ->join('d.site', 's')
                        ->andWhere('d.deletedAt IS NULL')
                        ->orderBy('d.isGateway', 'DESC')
                        ->addOrderBy('s.name', 'ASC')
                        ->addOrderBy('d.name', 'ASC');

                    if ($options['excludeDeviceId']) {
                        $qb->andWhere('d.id != :excludeDeviceId')
                            ->setParameter('excludeDeviceId', $options['excludeDeviceId']);
                    }

                    if ($options['vendors']) {
                        $qb->join('d.vendor', 'v')
                            ->andWhere('v.id IN (:vendors)')
                            ->setParameter('vendors', $options['vendors']);
                    }

                    if ($options['excludeNetFlowDevices']) {
                        $qb->andWhere('d.netFlowActiveVersion IS NULL')
                            ->andWhere('d.netFlowSynchronized = true');
                    }

                    if ($options['excludeNonRouterDevices']) {
                        // This excludes devices like USG. It can be detected using osVersion:
                        // Router example: EdgeRouter.ER-e200.v1.7.2.4824271.151111.1442
                        // USG example: UniFiSecurityGateway.ER-e120.v4.3.33.4936086.161203.2031
                        $qb->andWhere('d.osVersion LIKE :osVersionPattern')
                            ->setParameter('osVersionPattern', '%Router%');
                    }

                    return $qb;
                };
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }
}
