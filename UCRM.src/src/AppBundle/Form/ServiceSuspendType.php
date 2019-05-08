<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\ServiceStopReason;
use AppBundle\Form\Data\ServiceSuspendData;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSuspendType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'stopReason',
            EntityType::class,
            [
                'class' => ServiceStopReason::class,
                'choice_label' => 'name',
                'placeholder' => 'Make a choice.',
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('ssr')
                        ->andWhere('ssr.id NOT IN (:system)')
                        ->orderBy('ssr.name')
                        ->setParameter('system', ServiceStopReason::SYSTEM_REASONS);
                },
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ServiceSuspendData::class,
            ]
        );
    }
}
