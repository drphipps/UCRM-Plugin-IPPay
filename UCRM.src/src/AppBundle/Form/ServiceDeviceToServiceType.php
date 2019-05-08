<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Client;
use AppBundle\Entity\Service;
use AppBundle\Form\Data\ServiceDeviceToServiceData;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceDeviceToServiceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'service',
            EntityType::class,
            [
                'class' => Service::class,
                'choice_label' => 'name',
                'placeholder' => 'Make a choice.',
                'query_builder' => function (EntityRepository $repository) use ($options) {
                    return $repository->createQueryBuilder('s')
                        ->join('s.tariff', 't')
                        ->andWhere('s.client = :client')
                        ->andWhere('s.deletedAt IS NULL')
                        ->orderBy('s.name, t.name')
                        ->setParameter('client', $options['client']);
                },
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
                'data_class' => ServiceDeviceToServiceData::class,
            ]
        );
    }
}
