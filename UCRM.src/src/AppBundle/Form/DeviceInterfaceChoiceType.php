<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\DeviceInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeviceInterfaceChoiceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'class' => DeviceInterface::class,
                'placeholder' => 'Make a choice.',
                'deviceInterface' => null,
                'choice_label' => 'nameForView',
                'group_by' => function (DeviceInterface $interface) {
                    return $interface->getDevice()->getSite()->getName();
                },
            ]
        );

        $resolver->setAllowedTypes('deviceInterface', ['null', DeviceInterface::class]);

        $resolver->setDefault(
            'query_builder',
            function (Options $options) {
                /** @var DeviceInterface $current */
                $current = $options['deviceInterface'];

                return function (EntityRepository $repository) use ($current) {
                    $qb = $repository->createQueryBuilder('i');
                    $qb
                        ->addSelect('d')
                        ->join('i.device', 'd')
                        ->join('d.site', 's')
                        ->where(
                            's.deletedAt IS NULL',
                            'd.deletedAt IS NULL',
                            'i.deletedAt IS NULL',
                            'i.enabled = TRUE',
                            'i.allowClientConnection = TRUE'
                        );

                    if ($current) {
                        $qb
                            ->orWhere('i = :current')
                            ->setParameter('current', $current);
                    }

                    return $qb
                        ->addGroupBy('i.id')
                        ->addGroupBy('d.id')
                        ->addOrderBy('d.name', 'ASC')
                        ->addOrderBy('i.name', 'ASC');
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
