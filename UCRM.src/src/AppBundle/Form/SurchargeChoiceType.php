<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Surcharge;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SurchargeChoiceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'class' => Surcharge::class,
                'choice_label' => 'name',
                'placeholder' => 'Make a choice.',
                'surcharge' => null,
            ]
        );

        $resolver->setAllowedTypes('surcharge', ['null', Surcharge::class]);

        $resolver->setDefault(
            'query_builder',
            function (Options $options) {
                /** @var Surcharge|null $current */
                $current = $options['surcharge'];

                return function (EntityRepository $repository) use ($current) {
                    $qb = $repository->createQueryBuilder('s');
                    $qb->where('s.deletedAt IS NULL');

                    if ($current) {
                        $qb
                            ->orWhere('s = :current')
                            ->setParameter('current', $current);
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
