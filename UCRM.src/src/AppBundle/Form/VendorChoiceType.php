<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Vendor;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VendorChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'class' => Vendor::class,
                'choice_label' => 'name',
                'placeholder' => 'Make a choice.',
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('v', 'v.id')
                        ->orderBy('v.id', 'ASC');
                },
            ]
        );
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
