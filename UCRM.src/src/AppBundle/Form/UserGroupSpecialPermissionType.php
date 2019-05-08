<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\UserGroupSpecialPermission;
use AppBundle\Security\SpecialPermission;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class UserGroupSpecialPermissionType.
 */
class UserGroupSpecialPermissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'permission',
            ChoiceType::class,
            [
                'choices' => [
                    'deny' => SpecialPermission::DENIED,
                    'allow' => SpecialPermission::ALLOWED,
                ],
                'expanded' => true,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => UserGroupSpecialPermission::class,
            ]
        );
    }
}
