<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminChoiceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'class' => User::class,
                'user' => null,
                'users' => new ArrayCollection(),
                'choice_label' => 'nameForView',
                'placeholder' => 'Make a choice.',
                'query_builder' => function (Options $options) {
                    try {
                        /** @var User|null $user */
                        $user = $options['user'];
                        /** @var ArrayCollection|User[] $users */
                        $users = $options['users'];
                    } catch (InvalidOptionsException $ioe) {
                        $user = null;
                        $users = new ArrayCollection();
                    }

                    return function (EntityRepository $er) use ($user, $users) {
                        $qb = $er->createQueryBuilder('u')
                            ->addSelect('c')
                            ->andWhere('u.role IN (:role)')
                            // client join required because 1:1 assoc triggers query to client for each user otherwise
                            ->leftJoin('u.client', 'c')
                            ->orderBy('u.firstName, u.lastName')
                            ->setParameter('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN]);

                        if (! $users->isEmpty()) {
                            $qb->andWhere('u.deletedAt IS NULL OR u.id IN (:users)')
                                ->andWhere('u.isActive = true OR u.id IN (:users)')
                                ->setParameter('users', $users->toArray());
                        } elseif ($user) {
                            $qb->andWhere('u.deletedAt IS NULL OR u.id = :user')
                                ->andWhere('u.isActive = true OR u.id = :user')
                                ->setParameter('user', $user);
                        } else {
                            $qb->andWhere('u.deletedAt IS NULL')
                                ->andWhere('u.isActive = true');
                        }

                        return $qb;
                    };
                },
            ]
        );

        $resolver->setAllowedTypes('user', ['null', User::class]);
        $resolver->setAllowedTypes('users', Collection::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }
}
