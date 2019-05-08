<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Service;
use AppBundle\Form\Data\ServicePostponeData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServicePostponeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Service $service */
        $builder
            ->add(
                'postponeUntil',
                DateType::class,
                [
                    'required' => true,
                    'widget' => 'single_text',
                    'html5' => false,
                    'label' => 'Suspended from',
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => ServicePostponeData::class,
            ]
        );
    }
}
