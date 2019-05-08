<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\CustomAttribute;
use AppBundle\Util\Strings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomAttributeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class);

        if ($options['include_attribute_type']) {
            $builder->add(
                'attributeType',
                ChoiceType::class,
                [
                    'choices' => array_flip(CustomAttribute::ATTRIBUTE_TYPES),
                    'placeholder' => 'Make a choice.',
                ]
            );
        }

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                /** @var CustomAttribute $data */
                $data = $event->getData();
                $data->setKey(Strings::slugifyCamelCase($data->getName()));
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => CustomAttribute::class,
                'include_attribute_type' => true,
            ]
        );

        $resolver->setAllowedTypes('include_attribute_type', 'bool');
    }
}
