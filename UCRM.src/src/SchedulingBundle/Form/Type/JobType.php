<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace SchedulingBundle\Form\Type;

use AppBundle\Entity\User;
use AppBundle\Form\AdminChoiceType;
use AppBundle\Form\ClientChoiceType;
use Doctrine\ORM\EntityRepository;
use SchedulingBundle\Entity\Job;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class);

        $builder->add(
            'status',
            ChoiceType::class,
            [
                'choices' => array_flip(Job::STATUSES),
                'choice_attr' => function (int $status) {
                    return [
                        'data-status-class' => Job::STATUS_CLASSES[$status] ?? '',
                    ];
                },
            ]
        );

        $builder->add(
            'date',
            DateTimeType::class,
            [
                'required' => (bool) $options['assignable_user'],
                'widget' => 'single_text',
                'html5' => false,
            ]
        );

        $builder->add(
            'duration',
            JobDurationType::class,
            [
                'required' => false,
                'label' => 'Duration',
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ]
        );

        if ($options['assignable_user']) {
            $builder->add(
                'assignedUser',
                AdminChoiceType::class,
                [
                    'required' => true,
                    'placeholder' => null,
                    'query_builder' => function (EntityRepository $er) use ($options) {
                        return $er->createQueryBuilder('u')
                            ->andWhere('u.id = :user')
                            ->setParameter('user', $options['assignable_user']);
                    },
                ]
            );
        } else {
            $builder->add(
                'assignedUser',
                AdminChoiceType::class,
                [
                    'required' => false,
                    'group_by' => function (User $user) {
                        return $user->getGroup() ? $user->getGroup()->getName() : null;
                    },
                    'user' => isset($options['data']) ? $options['data']->getAssignedUser() : null,
                ]
            );
        }

        $builder->add(
            'client',
            ClientChoiceType::class,
            [
                'required' => false,
                'include_leads' => true,
            ]
        );

        $builder->add(
            'address',
            TextType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'gpsLat',
            NumberType::class,
            [
                'label' => 'GPS latitude',
                'scale' => 12,
                'required' => false,
            ]
        );

        $builder->add(
            'gpsLon',
            NumberType::class,
            [
                'label' => 'GPS longitude',
                'scale' => 12,
                'required' => false,
            ]
        );

        $builder->add(
            'resolveGps',
            ButtonType::class,
            [
                'label' => 'Resolve GPS',
            ]
        );

        $builder->add(
            'tasks',
            CollectionType::class,
            [
                'entry_type' => JobTaskType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ]
        );

        $builder->add(
            'attachmentFiles',
            FileType::class,
            [
                'required' => false,
                'data_class' => null,
                'multiple' => true,
            ]
        );

        $builder->add(
            'public',
            CheckboxType::class,
            [
                'label' => 'Visible in Client Zone',
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Job::class,
                'assignable_user' => null,
            ]
        );

        $resolver->setAllowedTypes('assignable_user', ['null', User::class]);
    }
}
