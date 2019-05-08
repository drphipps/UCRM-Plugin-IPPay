<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\Tax;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxChoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (! $options['multiple'] && $options['forceArrayModel']) {
            $builder->addModelTransformer(
                new CallbackTransformer(
                    function ($value) {
                        return $value[0] ?? null;
                    },
                    function ($value) {
                        return $value ? [$value] : [];
                    }
                )
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'class' => Tax::class,
                'choice_label' => 'name',
                'placeholder' => 'Make a choice.',
                'taxes' => [],
                'removeTaxes' => [],
                'forceArrayModel' => false,
            ]
        );

        $resolver->setAllowedTypes('taxes', 'array');
        $resolver->setAllowedTypes('forceArrayModel', 'bool');

        $resolver->setDefault(
            'query_builder',
            function (Options $options) {
                $taxes = $options['taxes'];
                $removeTaxes = $options['removeTaxes'];

                return function (EntityRepository $repository) use ($taxes, $removeTaxes) {
                    $qb = $repository->createQueryBuilder('t');
                    $qb->where('t.deletedAt IS NULL');

                    if ($taxes) {
                        $qb->orWhere('t IN (:taxes)')
                            ->setParameter('taxes', $taxes);
                    }

                    if ($removeTaxes) {
                        $qb->andWhere('t NOT IN (:removeTaxes)')
                            ->setParameter('removeTaxes', $removeTaxes);
                    }

                    return $qb;
                };
            }
        );
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
