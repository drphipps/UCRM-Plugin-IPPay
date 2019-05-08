<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Option;
use AppBundle\Form\Data\Settings\SyncData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingSyncType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'syncEnabled',
                CheckboxType::class,
                [
                    'label' => Option::NAMES[Option::SYNC_ENABLED],
                    'required' => false,
                ]
            )
            ->add(
                'syncFrequency',
                ChoiceType::class,
                [
                    'label' => Option::NAMES[Option::SYNC_FREQUENCY],
                    'choices' => array_flip(Option::SYNC_FREQUENCIES),
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => SyncData::class,
            ]
        );
    }
}
