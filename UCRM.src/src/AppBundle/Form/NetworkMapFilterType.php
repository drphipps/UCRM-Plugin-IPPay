<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Component\Map\Request\NetworkMapRequest;
use AppBundle\Entity\Site;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NetworkMapFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'sites',
            EntityType::class,
            [
                'class' => Site::class,
                'choice_label' => 'name',
                'multiple' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->andWhere('s.deletedAt IS NULL')
                        ->orderBy('s.name');
                },
                'required' => false,
                'attr' => [
                    'placeholder' => 'Pick a site',
                ],
            ]
        );

        $builder->add(
            'excludeLeads',
            CheckboxType::class,
            [
                'required' => false,
                'label' => 'Hide client leads',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => NetworkMapRequest::class,
            ]
        );
    }
}
