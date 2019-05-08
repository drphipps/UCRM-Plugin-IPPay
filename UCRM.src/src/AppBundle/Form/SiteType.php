<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Site;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('address')
            ->add(
                'gpsLat',
                NumberType::class,
                [
                    'label' => 'GPS latitude',
                    'scale' => 12,
                ]
            )
            ->add(
                'gpsLon',
                NumberType::class,
                [
                    'label' => 'GPS longitude',
                    'scale' => 12,
                ]
            )
            ->add('contactInfo')
            ->add('notes')
            ->add(
                'resolveGps',
                ButtonType::class,
                [
                    'label' => 'Resolve GPS',
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Site::class,
            ]
        );
    }
}
