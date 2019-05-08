<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\ClientContact;
use AppBundle\Entity\ContactType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'email',
                EmailType::class,
                [
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Email',
                    ],
                ]
            )
            ->add(
                'phone',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Phone',
                    ],
                ]
            )
            ->add(
                'name',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Name',
                    ],
                ]
            )
            ->add(
                'types',
                EntityType::class,
                [
                    'multiple' => true,
                    'label' => 'Contact type',
                    'class' => ContactType::class,
                    'choice_label' => 'name',
                    'placeholder' => 'Make a choice.',
                    'attr' => [
                        'placeholder' => 'Contact type',
                    ],
                    'required' => false,
                    'query_builder' => function (EntityRepository $repository) {
                        return $repository
                            ->createQueryBuilder('ct')
                            ->orderBy('ct.name', 'ASC');
                    },
                ]
            )
            ->add(
                'isLogin',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'Use email as username',
                ]
            );
        // @todo translations when frontend is determined
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => ClientContact::class,
            ]
        );
    }
}
