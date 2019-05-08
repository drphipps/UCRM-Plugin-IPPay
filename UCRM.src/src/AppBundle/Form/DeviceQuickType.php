<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Form;

use AppBundle\Entity\Device;
use AppBundle\Entity\ServiceDevice;
use AppBundle\Entity\Site;
use AppBundle\Entity\Vendor;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DeviceQuickType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'required' => true,
            ]
        );

        $builder->add(
            'vendorId',
            ChoiceType::class,
            [
                'label' => 'Vendor',
                'placeholder' => 'Make a choice.',
                'choices' => array_flip(Vendor::TYPES),
                'mapped' => false,
                'constraints' => new Assert\NotNull(),
            ]
        );

        $builder->add(
            'site',
            EntityType::class,
            [
                'placeholder' => 'Make a choice.',
                'class' => Site::class,
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->andWhere('s.deletedAt IS NULL')
                        ->orderBy('s.name');
                },
            ]
        );

        $builder->add(
            'loginUsername',
            TextType::class,
            [
                'required' => true,
            ]
        );

        $builder->add(
            'loginPassword',
            PasswordType::class,
            [
                'required' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
            ]
        );

        $builder->add(
            'ipRange',
            IpRangeType::class,
            [
                'required' => true,
                'mapped' => false,
                'constraints' => new Assert\Valid(),
            ]
        );

        $builder->add(
            'sshPort',
            IntegerType::class,
            [
                'label' => 'SSH port',
                'required' => true,
                'data' => ServiceDevice::DEFAULT_SSH_PORT,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Device::class,
            ]
        );
    }
}
