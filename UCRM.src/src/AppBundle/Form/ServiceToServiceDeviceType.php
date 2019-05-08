<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Client;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Form\Data\ServiceToServiceDeviceData;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class ServiceToServiceDeviceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'serviceDevice',
            EntityType::class,
            [
                'class' => ServiceDevice::class,
                'choice_label' => 'nameForView',
                'placeholder' => 'Make a choice.',
                'query_builder' => function (EntityRepository $repository) use ($options) {
                    return $repository->createQueryBuilder('sd')
                        ->addSelect('ip, v')
                        ->join('sd.service', 's')
                        ->join('sd.vendor', 'v')
                        ->leftJoin('sd.serviceIps', 'ip')
                        ->andWhere('s.deletedAt IS NOT NULL')
                        ->andWhere('s.client = :client')
                        ->setParameter('client', $options['client']);
                },
                'constraints' => $options['allow_null'] ? null : new NotNull(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('client');
        $resolver->setAllowedTypes('client', Client::class);

        $resolver->setDefaults(
            [
                'data_class' => ServiceToServiceDeviceData::class,
                'allow_null' => false,
            ]
        );
    }
}
