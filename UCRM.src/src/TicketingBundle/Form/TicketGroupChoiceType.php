<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace TicketingBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TicketingBundle\Entity\TicketGroup;

class TicketGroupChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'class' => TicketGroup::class,
                'choice_label' => 'name',
                'placeholder' => 'Make a choice.',
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('tg');
                },
            ]
        );
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
